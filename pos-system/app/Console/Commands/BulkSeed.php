<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Load-test data generator: php artisan pos:bulk-seed --products=100000 --sales=200000
 * Uses multi-row INSERTs in chunks (thousands of rows per statement) – no Eloquent.
 */
class BulkSeed extends Command
{
    protected $signature = 'pos:bulk-seed {--products=50000} {--sales=0} {--customers=5000}';
    protected $description = 'Generate large volumes of demo data to stress-test the POS';

    private const WORDS = ['Cement', 'Steel', 'Rod', 'PVC', 'Pipe', 'Elbow', 'Tee', 'Socket', 'Bulb', 'LED', 'Wire', 'Switch',
        'Hammer', 'Tape', 'Screw', 'Nail', 'Bolt', 'Nut', 'Washer', 'Paint', 'Brush', 'Roller', 'Plug', 'Glove', 'Drill',
        'Bit', 'Saw', 'Blade', 'Valve', 'Tap', 'Hose', 'Clamp', 'Hinge', 'Lock', 'Handle', 'Chain', 'Rope', 'Sheet', 'Tile', 'Adhesive'];
    private const SIZES = ['6mm', '8mm', '10mm', '12mm', '1/2"', '3/4"', '1"', '2"', '3"', '4"', '1L', '4L', '5L', '50kg', '25kg', '5m', '10m'];

    public function handle(): int
    {
        DB::disableQueryLog();
        $n = (int) $this->option('products');
        $branchIds = DB::table('branches')->pluck('id')->all();
        $catIds = DB::table('categories')->pluck('id')->all();
        $userId = DB::table('users')->value('id');
        if (! $branchIds || ! $catIds || ! $userId) {
            $this->error('Run php artisan db:seed first.');

            return self::FAILURE;
        }

        $start = (int) DB::table('products')->max('id') + 1;
        $now = now()->toDateTimeString();
        $bar = $this->output->createProgressBar($n);
        $this->info("Inserting {$n} products...");

        for ($i = 0; $i < $n; $i += 2000) {
            $rows = [];
            $stock = [];
            for ($j = 0; $j < min(2000, $n - $i); $j++) {
                $k = $start + $i + $j;
                $cost = mt_rand(50, 20000);
                $rows[] = [
                    'code' => 'BLK'.$k, 'barcode' => '9'.str_pad((string) $k, 12, '0', STR_PAD_LEFT),
                    'name' => self::WORDS[array_rand(self::WORDS)].' '.self::WORDS[array_rand(self::WORDS)].' '.self::SIZES[array_rand(self::SIZES)].' #'.$k,
                    'category_id' => $catIds[array_rand($catIds)], 'unit' => 'pcs',
                    'cost_price' => $cost, 'sell_price' => round($cost * 1.25), 'reorder_level' => 5,
                    'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
                ];
            }
            DB::table('products')->insert($rows);
            $ids = DB::table('products')->where('id', '>=', $start + $i)->where('code', 'like', 'BLK%')->orderBy('id')->limit(count($rows))->pluck('id');
            foreach ($ids as $id) {
                foreach ($branchIds as $b) {
                    $stock[] = ['branch_id' => $b, 'product_id' => $id, 'qty' => mt_rand(0, 500), 'updated_at' => $now];
                }
            }
            foreach (array_chunk($stock, 4000) as $c) {
                DB::table('product_stocks')->insertOrIgnore($c);
            }
            $bar->advance(count($rows));
        }
        $bar->finish();
        $this->newLine();

        $c = (int) $this->option('customers');
        if ($c > 0) {
            $this->info("Inserting {$c} customers...");
            for ($i = 0; $i < $c; $i += 2000) {
                $rows = [];
                for ($j = 0; $j < min(2000, $c - $i); $j++) {
                    $rows[] = ['name' => 'Customer '.($i + $j + 1), 'phone' => '07'.mt_rand(10000000, 99999999), 'created_at' => $now, 'updated_at' => $now];
                }
                DB::table('customers')->insert($rows);
            }
        }

        $s = (int) $this->option('sales');
        if ($s > 0) {
            $this->seedSales($s, $branchIds, $userId);
        }

        DB::statement('ANALYZE TABLE '.implode(', ', array_map('px', ['products', 'product_stocks', 'sales', 'sale_items', 'customers'])));
        $this->info('Done.');

        \App\Models\Product::flushCounts();

        return self::SUCCESS;
    }

    /** Historical bills spread over the last 365 days (does not touch stock). */
    private function seedSales(int $s, array $branchIds, int $userId): void
    {
        $this->info("Inserting {$s} historical sales...");
        $products = DB::table('products')->inRandomOrder()->limit(2000)->get(['id', 'name', 'sell_price', 'cost_price'])->all();
        $bar = $this->output->createProgressBar($s);
        $seq = [];

        for ($i = 0; $i < $s; $i += 1000) {
            DB::transaction(function () use ($i, $s, $branchIds, $userId, $products, &$seq) {
                $batch = min(1000, $s - $i);
                $sales = [];
                $lines = [];
                for ($j = 0; $j < $batch; $j++) {
                    $b = $branchIds[array_rand($branchIds)];
                    $seq[$b] = ($seq[$b] ?? 0) + 1;
                    $ts = now()->subMinutes(mt_rand(0, 525600));
                    $cnt = mt_rand(1, 6);
                    $sub = 0;
                    $cost = 0;
                    $ls = [];
                    for ($k = 0; $k < $cnt; $k++) {
                        $p = $products[array_rand($products)];
                        $q = mt_rand(1, 5);
                        $sub += $q * $p->sell_price;
                        $cost += $q * $p->cost_price;
                        $ls[] = ['product_id' => $p->id, 'name' => $p->name, 'qty' => $q, 'unit_price' => $p->sell_price, 'cost_price' => $p->cost_price, 'line_total' => $q * $p->sell_price];
                    }
                    $tax = round($sub * 0.15, 2);
                    $sales[] = [
                        'bill_no' => 'BLK'.$b.'-'.str_pad((string) ($i + $j + 1), 8, '0', STR_PAD_LEFT), 'branch_id' => $b, 'user_id' => $userId,
                        'item_count' => $cnt, 'subtotal' => $sub, 'discount_type' => 'amount', 'discount_value' => 0, 'discount_amount' => 0,
                        'tax_rate' => 15, 'tax_amount' => $tax, 'total' => $sub + $tax, 'cost_total' => $cost,
                        'payment_method' => ['cash', 'cash', 'cash', 'card', 'bank'][mt_rand(0, 4)], 'paid_amount' => $sub + $tax,
                        'change_amount' => 0, 'status' => 'completed', 'sale_date' => $ts->toDateString(),
                        'created_at' => $ts, 'updated_at' => $ts,
                    ];
                    $lines[] = $ls;
                }
                DB::table('sales')->insert($sales);
                $firstId = (int) DB::getPdo()->lastInsertId(); // multi-row insert returns the FIRST id (innodb_autoinc_lock_mode 1/2 consecutive)
                $items = [];
                foreach ($lines as $idx => $ls) {
                    foreach ($ls as $l) {
                        $items[] = $l + ['sale_id' => $firstId + $idx];
                    }
                }
                foreach (array_chunk($items, 3000) as $c) {
                    DB::table('sale_items')->insert($c);
                }
            });
            $bar->advance(min(1000, $s - $i));
        }
        $bar->finish();
        $this->newLine();
    }
}
