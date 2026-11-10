<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hardware POS schema.
 *
 * Index design notes (every index below backs a specific query):
 *  - products.(is_active, category_id, name)  -> POS grid filtered by category, ordered by name
 *  - products.(is_active, name)               -> POS grid "All items", ordered by name
 *  - products.code / products.barcode UNIQUE  -> barcode scan = single index lookup
 *  - products FULLTEXT(name, code, barcode)   -> word search that stays fast at 100k+ rows
 *  - product_stocks PK(branch_id, product_id) -> stock join for the current branch
 *  - sales.(branch_id, status, sale_date)     -> reports / daily summaries via range scan
 *  - stock_movements.(product_id, branch_id, id) -> per-product ledger, newest first
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100);
            $t->string('code', 10)->unique()->comment('Bill number prefix, e.g. HS');
            $t->string('address')->nullable();
            $t->string('phone', 30)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('branch_id')->nullable()->after('role')->constrained('branches')->nullOnDelete();
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->string('key', 60)->primary();
            $t->text('value')->nullable();
        });

        Schema::create('doc_sequences', function (Blueprint $t) {
            $t->string('key', 40)->primary()->comment('e.g. sale:1, purchase:1');
            $t->unsignedBigInteger('last_no')->default(0);
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name', 80)->unique();
            $t->string('icon', 40)->default('fa-box');
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['is_active', 'sort_order']);
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->string('barcode', 64)->nullable()->unique();
            $t->string('name', 150);
            $t->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $t->string('unit', 15)->default('pcs');
            $t->decimal('cost_price', 12, 2)->default(0);
            $t->decimal('sell_price', 12, 2)->default(0);
            $t->decimal('reorder_level', 12, 3)->default(0);
            $t->string('image', 150)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();

            $t->index(['is_active', 'category_id', 'name'], 'products_grid_cat_idx');
            $t->index(['is_active', 'name'], 'products_grid_all_idx');
            $t->index('name');
        });
        DB::statement('ALTER TABLE '.px('products').' ADD FULLTEXT '.px('products_ft').' (name, code, barcode)');

        Schema::create('product_stocks', function (Blueprint $t) {
            $t->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $t->decimal('qty', 14, 3)->default(0);
            $t->timestamp('updated_at')->nullable();
            $t->primary(['branch_id', 'product_id']);
            $t->index(['branch_id', 'qty']);
        });

        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('phone', 30)->nullable()->index();
            $t->string('email', 120)->nullable();
            $t->string('address')->nullable();
            $t->decimal('credit_limit', 14, 2)->default(0);
            $t->decimal('balance', 14, 2)->default(0)->comment('Amount the customer owes');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index('name');
        });

        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('contact_person', 120)->nullable();
            $t->string('phone', 30)->nullable()->index();
            $t->string('email', 120)->nullable();
            $t->string('address')->nullable();
            $t->decimal('balance', 14, 2)->default(0)->comment('Amount we owe the supplier');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index('name');
        });

        Schema::create('sales', function (Blueprint $t) {
            $t->id();
            $t->string('bill_no', 30)->unique();
            $t->foreignId('branch_id')->constrained('branches');
            $t->foreignId('user_id')->constrained('users');
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->unsignedInteger('item_count')->default(0);
            $t->decimal('subtotal', 14, 2);
            $t->string('discount_type', 10)->default('amount')->comment('percent|amount');
            $t->decimal('discount_value', 12, 2)->default(0);
            $t->decimal('discount_amount', 14, 2)->default(0);
            $t->decimal('tax_rate', 5, 2)->default(0);
            $t->decimal('tax_amount', 14, 2)->default(0);
            $t->decimal('total', 14, 2);
            $t->decimal('cost_total', 14, 2)->default(0);
            $t->string('payment_method', 15)->default('cash')->comment('cash|card|credit|bank');
            $t->decimal('paid_amount', 14, 2)->default(0);
            $t->decimal('change_amount', 14, 2)->default(0);
            $t->string('status', 12)->default('completed')->comment('completed|void');
            $t->string('note')->nullable();
            $t->date('sale_date');
            $t->timestamps();

            $t->index(['branch_id', 'status', 'sale_date'], 'sales_report_idx');
            $t->index(['sale_date', 'status']);
            $t->index(['customer_id', 'id']);
        });

        Schema::create('sale_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->string('name', 150);
            $t->decimal('qty', 12, 3);
            $t->decimal('unit_price', 12, 2);
            $t->decimal('cost_price', 12, 2)->default(0);
            $t->decimal('line_total', 14, 2);
            $t->index(['product_id', 'sale_id']);
        });

        Schema::create('held_sales', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users');
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->string('label', 80)->nullable();
            $t->json('payload');
            $t->unsignedInteger('item_count')->default(0);
            $t->decimal('total', 14, 2)->default(0);
            $t->timestamp('created_at')->useCurrent();
            $t->index(['branch_id', 'id']);
        });

        Schema::create('purchases', function (Blueprint $t) {
            $t->id();
            $t->string('ref_no', 30)->unique();
            $t->foreignId('supplier_id')->constrained('suppliers');
            $t->foreignId('branch_id')->constrained('branches');
            $t->foreignId('user_id')->constrained('users');
            $t->date('purchase_date');
            $t->string('invoice_no', 60)->nullable();
            $t->decimal('subtotal', 14, 2);
            $t->decimal('discount', 14, 2)->default(0);
            $t->decimal('total', 14, 2);
            $t->decimal('paid', 14, 2)->default(0);
            $t->string('note')->nullable();
            $t->timestamps();
            $t->index(['branch_id', 'purchase_date']);
            $t->index(['supplier_id', 'id']);
        });

        Schema::create('purchase_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->decimal('qty', 12, 3);
            $t->decimal('unit_cost', 12, 2);
            $t->decimal('line_total', 14, 2);
            $t->index('product_id');
        });

        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->constrained('branches');
            $t->foreignId('product_id')->constrained('products');
            $t->string('type', 12)->comment('sale|purchase|adjust|void|opening');
            $t->decimal('qty', 14, 3)->comment('signed change');
            $t->decimal('balance_after', 14, 3);
            $t->string('ref_type', 20)->nullable();
            $t->unsignedBigInteger('ref_id')->nullable();
            $t->string('note')->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['product_id', 'branch_id', 'id']);
            $t->index(['branch_id', 'id']);
        });

        Schema::create('customer_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $t->foreignId('branch_id')->constrained('branches');
            $t->foreignId('user_id')->constrained('users');
            $t->decimal('amount', 14, 2);
            $t->string('method', 15)->default('cash');
            $t->string('note')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['customer_id', 'id']);
        });

        Schema::create('supplier_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $t->foreignId('branch_id')->constrained('branches');
            $t->foreignId('user_id')->constrained('users');
            $t->decimal('amount', 14, 2);
            $t->string('method', 15)->default('cash');
            $t->string('note')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['supplier_id', 'id']);
        });
    }

    public function down(): void
    {
        foreach ([
            'supplier_payments', 'customer_payments', 'stock_movements', 'purchase_items', 'purchases',
            'held_sales', 'sale_items', 'sales', 'suppliers', 'customers', 'product_stocks', 'products',
            'categories', 'doc_sequences', 'settings',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('branch_id');
        });
        Schema::dropIfExists('branches');
    }
};
