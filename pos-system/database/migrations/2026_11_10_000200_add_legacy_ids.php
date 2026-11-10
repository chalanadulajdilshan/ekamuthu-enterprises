<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links POS rows to the row they were imported from in the main Ekamuthu system
 * (department_master, item_master, customer_master, supplier_master).
 * UNIQUE so `php artisan pos:import-ekamuthu` can be re-run safely: it updates
 * instead of creating duplicates.
 */
return new class extends Migration
{
    private const TABLES = ['branches', 'products', 'customers', 'suppliers'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedInteger('legacy_id')->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropUnique($table.'_legacy_id_unique');
                $t->dropColumn('legacy_id');
            });
        }
    }
};
