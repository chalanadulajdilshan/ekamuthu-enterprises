<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual cash book entries (expenses, bank deposits / withdrawals, float, other income).
 * Sales, customer / supplier payments and purchases are read from their own tables,
 * so only cash movements that have no other record are stored here.
 * Index (branch_id, entry_date) backs the cash book's date-range scan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cash_entries')) {
            return;
        }

        Schema::create('cash_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->constrained('branches');
            $t->foreignId('user_id')->constrained('users');
            $t->date('entry_date');
            $t->string('direction', 3)->comment('in|out');
            $t->string('type', 20)->comment('float|withdrawal|income|expense|deposit|drawing|other');
            $t->decimal('amount', 14, 2);
            $t->string('ref_no', 60)->nullable();
            $t->string('note', 200)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['branch_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_entries');
    }
};
