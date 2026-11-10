<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Setting;
use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One-way import from the main Ekamuthu system into the POS tables.
 *
 *   php artisan pos:import-ekamuthu
 *
 * Safe to re-run. Rows are matched on legacy_id, so it updates instead of duplicating,
 * and opening stock is only written for products created in THIS run, so a re-run
 * never overwrites stock the POS has already been selling from.
 *
 * Source -> POS
 *   department_master  -> branches
 *   item_master        -> products  (+ images from pos-system/uploads, + opening stock)
 *   customer_master    -> customers
 *   supplier_master    -> suppliers
 *   company_profile    -> store name / address / phone / VAT settings (only if not set yet)
 */
class ImportEkamuthu extends Command
{
    protected $signature = 'pos:import-ekamuthu
        {--only= : Comma list of parts: branches,products,customers,suppliers,settings}
        {--stock-source=batches : Opening stock from "batches" (old POS item_batches), "stock_master" (main system), or "none"}
        {--category=Others : POS category for imported items}';

    protected $description = 'Import branches, items, customers, suppliers and store settings from the main Ekamuthu system';

    private Connection $src;

    public function handle(): int
    {
        DB::disableQueryLog();
        $this->src = DB::connection('ekamuthu');

        $parts = $this->option('only')
            ? array_map('trim', explode(',', $this->option('only')))
            : ['settings', 'branches', 'products', 'customers', 'suppliers'];

        $adminId = DB::table('users')->where('role', 'admin')->value('id');
        if (! $adminId) {
            $this->error('Run "php artisan db:seed --force" first (creates the admin user).');

            return self::FAILURE;
        }

        foreach (['settings', 'branches', 'products', 'customers', 'suppliers'] as $part) {
            if (in_array($part, $parts, true)) {
                $this->{'import'.ucfirst($part)}((int) $adminId);
            }
        }

        foreach (['branches.active', 'categories.active', 'settings.all'] as $key) {
            Cache::forget($key);
        }
        \App\Models\Product::flushCounts();
        $this->info('Done.');

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------ settings

    private function importSettings(int $adminId): void
    {
        $c = $this->src->table('company_profile')->orderByDesc('is_active')->orderBy('id')->first();
        if (! $c) {
            return;
        }
        $saved = Setting::query()->pluck('value', 'key')->all();
        $vals = [
            'store_name' => trim((string) $c->name),
            'store_address' => trim((string) $c->address),
            'store_phone' => trim(implode(' / ', array_filter([$c->mobile_number_1, $c->mobile_number_2]))),
        ];
        // Only fill what the admin has not set themselves.
        $vals = array_filter($vals, fn ($v, $k) => $v !== '' && ! isset($saved[$k]), ARRAY_FILTER_USE_BOTH);
        if (! isset($saved['tax_rate'])) {
            $vals['tax_rate'] = (string) ($c->is_vat ? (float) $c->vat_percentage : 0);
        }
        if ($vals) {
            Setting::store($vals);
        }
        $this->line('Settings: '.($vals ? implode(', ', array_keys($vals)) : 'nothing new'));
    }

    // ------------------------------------------------------------------ branches

    private function importBranches(int $adminId): void
    {
        $rows = $this->src->table('department_master')->where('is_active', 1)->orderBy('id')->get(['id', 'name']);
        $used = DB::table('branches')->pluck('code', 'id')->all();

        foreach ($rows as $i => $d) {
            $branch = Branch::query()->where('legacy_id', $d->id)->first();

            // The seeder makes one placeholder "Main Branch"; adopt it for the first department.
            if (! $branch && $i === 0) {
                $branch = Branch::query()->whereNull('legacy_id')->where('code', 'MAIN')->first();
            }

            $name = Str::title(Str::lower(trim($d->name)));
            if ($branch) {
                $upd = ['name' => $name, 'legacy_id' => $d->id];
                if ($branch->code === 'MAIN') { // placeholder code -> real one (HEAD OFFICE -> HO)
                    $upd['code'] = $this->uniqueCode($name, array_diff($used, ['MAIN']));
                    $used[$branch->id] = $upd['code'];
                }
                $branch->update($upd);
            } else {
                $code = $this->uniqueCode($name, $used);
                $branch = Branch::create(['name' => $name, 'code' => $code, 'legacy_id' => $d->id]);
                $used[$branch->id] = $code;
            }
        }
        $this->line('Branches: '.$rows->count());
    }

    /** "HEAD OFFICE" -> HO, "Bellanvila" -> BEL; bill numbers become HO-000001. */
    private function uniqueCode(string $name, array $used): string
    {
        $words = preg_split('/[^A-Za-z0-9]+/', strtoupper($name), -1, PREG_SPLIT_NO_EMPTY) ?: ['BR'];
        $base = count($words) > 1
            ? substr(implode('', array_map(fn ($w) => $w[0], $words)), 0, 4)
            : substr($words[0], 0, 3);
        $code = $base;
        for ($n = 2; in_array($code, $used, true); $n++) {
            $code = $base.$n;
        }

        return $code;
    }

    // ------------------------------------------------------------------ products

    private function importProducts(int $adminId): void
    {
        $categoryId = Category::query()->firstOrCreate(
            ['name' => $this->option('category')],
            ['icon' => 'fa-box', 'sort_order' => 99]
        )->id;

        $existing = DB::table('products')->get(['id', 'legacy_id', 'code', 'barcode', 'image']);
        $byLegacy = $existing->whereNotNull('legacy_id')->keyBy('legacy_id');
        $codes = $existing->pluck('legacy_id', 'code')->all();          // code => legacy_id|null
        $barcodes = $existing->whereNotNull('barcode')->pluck('legacy_id', 'barcode')->all();

        $imgFrom = base_path('uploads');
        $imgTo = public_path('uploads/products');
        if (! is_dir($imgTo)) {
            @mkdir($imgTo, 0775, true);
        }

        $created = [];
        $count = 0;
        $images = 0;
        $now = now();

        $this->src->table('item_master')->orderBy('id')->chunk(1000, function ($items) use (
            &$codes, &$barcodes, &$created, &$count, &$images, $byLegacy, $categoryId, $imgFrom, $imgTo, $now
        ) {
            $rows = [];
            foreach ($items as $it) {
                $code = trim((string) $it->code) !== '' ? trim((string) $it->code) : 'EK'.$it->id;
                // A code already used by a DIFFERENT product gets the source id appended.
                if (array_key_exists($code, $codes) && $codes[$code] != $it->id) {
                    $code = Str::limit($code, 30, '').'-'.$it->id;
                }
                $codes[$code] = $it->id;

                $barcode = trim((string) $it->barcode) ?: null;
                if ($barcode !== null && array_key_exists($barcode, $barcodes) && $barcodes[$barcode] != $it->id) {
                    $barcode = null; // duplicate barcode in the source: keep the first one
                }
                if ($barcode !== null) {
                    $barcodes[$barcode] = $it->id;
                }

                $image = $byLegacy[$it->id]->image ?? null;
                if (! $image && $it->image_file && is_file($imgFrom.'/'.$it->image_file)) {
                    $name = basename($it->image_file);
                    if (@copy($imgFrom.'/'.$it->image_file, $imgTo.'/'.$name)) {
                        $image = 'products/'.$name;
                        $images++;
                    }
                }

                $row = [
                    'legacy_id' => $it->id,
                    'code' => Str::limit($code, 40, ''),
                    'barcode' => $barcode ? Str::limit($barcode, 64, '') : null,
                    'name' => Str::limit(trim((string) $it->name) ?: $code, 150, ''),
                    'cost_price' => (float) $it->list_price,
                    'sell_price' => (float) $it->invoice_price,
                    'reorder_level' => max((float) $it->re_order_level, 0),
                    'is_active' => (int) $it->is_active === 1,
                    'updated_at' => $now,
                ];
                if (isset($byLegacy[$it->id])) {
                    // Existing: refresh master data, keep the category/unit chosen in the POS.
                    DB::table('products')->where('id', $byLegacy[$it->id]->id)->update($row + ($image ? ['image' => $image] : []));
                } else {
                    $rows[] = $row + ['category_id' => $categoryId, 'unit' => 'pcs', 'image' => $image, 'created_at' => $now];
                    $created[] = $it->id;
                }
                $count++;
            }
            if ($rows) {
                DB::table('products')->insert($rows);
            }
        });

        $this->line("Products: {$count} (".count($created)." new, {$images} images copied)");

        if ($created && $this->option('stock-source') !== 'none') {
            $this->importOpeningStock($created, $adminId);
        }
    }

    /** Opening stock for products created in this run only. */
    private function importOpeningStock(array $legacyIds, int $adminId): void
    {
        $source = $this->option('stock-source');
        $branchByDept = DB::table('branches')->whereNotNull('legacy_id')->pluck('id', 'legacy_id');
        $defaultBranch = (int) DB::table('branches')->orderBy('id')->value('id');
        $productByLegacy = DB::table('products')->whereIn('legacy_id', $legacyIds)->pluck('id', 'legacy_id');

        $deltas = []; // branch_id => [product_id => qty]
        foreach (array_chunk($legacyIds, 1000) as $ids) {
            $rows = $source === 'stock_master'
                ? $this->src->table('stock_master')->whereIn('item_id', $ids)
                    ->groupBy('item_id', 'department_id')
                    ->selectRaw('item_id, department_id, SUM(quantity) AS qty')->get()
                : $this->src->table('item_batches as b')->leftJoin('grn_master as g', 'g.id', '=', 'b.grn_id')
                    ->whereIn('b.item_id', $ids)->where('b.qty_remaining', '>', 0)
                    ->groupBy('b.item_id', 'g.department_id')
                    ->selectRaw('b.item_id, g.department_id, SUM(b.qty_remaining) AS qty')->get();

            foreach ($rows as $r) {
                if ((float) $r->qty <= 0 || ! isset($productByLegacy[$r->item_id])) {
                    continue;
                }
                $branchId = $branchByDept[$r->department_id] ?? $defaultBranch;
                $pid = $productByLegacy[$r->item_id];
                $deltas[$branchId][$pid] = ($deltas[$branchId][$pid] ?? 0) + (float) $r->qty;
            }
        }

        $lines = 0;
        foreach ($deltas as $branchId => $d) {
            foreach (array_chunk($d, 500, true) as $chunk) {
                DB::transaction(fn () => StockService::apply($branchId, $chunk, 'opening', 'import', null, $adminId, 'Opening stock (Ekamuthu import)'));
                $lines += count($chunk);
            }
        }
        $this->line("Opening stock: {$lines} product/branch lines from {$source}");
    }

    // ------------------------------------------------------------------ customers / suppliers

    private function importCustomers(int $adminId): void
    {
        $n = 0;
        $this->src->table('customer_master')->orderBy('id')->chunk(1000, function ($rows) use (&$n) {
            $now = now();
            $data = [];
            foreach ($rows as $c) {
                $name = trim((string) ($c->is_company && $c->company_name ? $c->company_name : $c->name));
                if ($name === '') {
                    continue;
                }
                $data[] = [
                    'legacy_id' => $c->id,
                    'name' => Str::limit($name, 120, ''),
                    'phone' => Str::limit(trim((string) $c->mobile_number), 30, '') ?: null,
                    'address' => Str::limit(trim((string) $c->address), 255, '') ?: null,
                    'is_active' => ! $c->is_blacklisted,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            // legacy_id is UNIQUE -> one statement inserts new rows and refreshes existing ones.
            DB::table('customers')->upsert($data, ['legacy_id'], ['name', 'phone', 'address', 'is_active', 'updated_at']);
            $n += count($data);
        });
        $this->line("Customers: {$n}");
    }

    private function importSuppliers(int $adminId): void
    {
        $now = now();
        $data = $this->src->table('supplier_master')->orderBy('id')->get()
            ->filter(fn ($s) => trim((string) $s->name) !== '')
            ->map(fn ($s) => [
                'legacy_id' => $s->id,
                'name' => Str::limit(trim($s->name), 120, ''),
                'contact_person' => Str::limit(trim((string) $s->contact_person), 120, '') ?: null,
                'phone' => Str::limit(trim((string) $s->mobile_number), 30, '') ?: null,
                'email' => Str::limit(trim((string) $s->email), 120, '') ?: null,
                'address' => Str::limit(trim((string) $s->address), 255, '') ?: null,
                'is_active' => (bool) $s->is_active,
                'created_at' => $now,
                'updated_at' => $now,
            ])->values()->all();

        foreach (array_chunk($data, 1000) as $chunk) {
            DB::table('suppliers')->upsert($chunk, ['legacy_id'], ['name', 'contact_person', 'phone', 'email', 'address', 'is_active', 'updated_at']);
        }
        $this->line('Suppliers: '.count($data));
    }
}
