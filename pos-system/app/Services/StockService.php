<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The ONLY place stock quantities change.
 *
 * - Rows are locked in product_id order (SELECT ... FOR UPDATE) so concurrent
 *   tills cannot oversell and cannot deadlock each other.
 * - All quantities are updated with ONE statement and all ledger rows are
 *   written with ONE bulk insert, regardless of how many lines a bill has.
 * - Must be called inside a DB transaction.
 */
class StockService
{
    /**
     * @param  array<int, float>  $deltas  product_id => signed qty change
     * @return array<int, float> product_id => new balance
     */
    public static function apply(
        int $branchId,
        array $deltas,
        string $type,
        ?string $refType = null,
        ?int $refId = null,
        ?int $userId = null,
        ?string $note = null,
        bool $preventNegative = false,
    ): array {
        $deltas = array_filter($deltas, fn ($q) => abs((float) $q) > 0.0000001);
        if (! $deltas) {
            return [];
        }
        ksort($deltas);
        $ids = array_keys($deltas);
        $now = now();

        DB::table('product_stocks')->insertOrIgnore(array_map(
            fn ($id) => ['branch_id' => $branchId, 'product_id' => $id, 'qty' => 0, 'updated_at' => $now],
            $ids
        ));

        $current = DB::table('product_stocks')
            ->where('branch_id', $branchId)->whereIn('product_id', $ids)
            ->orderBy('product_id')->lockForUpdate()
            ->pluck('qty', 'product_id');

        $balances = [];
        $short = [];
        foreach ($deltas as $pid => $delta) {
            $new = round((float) ($current[$pid] ?? 0) + (float) $delta, 3);
            if ($preventNegative && $delta < 0 && $new < 0) {
                $short[] = $pid;
            }
            $balances[$pid] = $new;
        }

        if ($short) {
            $names = DB::table('products')->whereIn('id', $short)->pluck('name')->implode(', ');
            throw ValidationException::withMessages(['stock' => "Not enough stock for: {$names}"]);
        }

        // Single UPDATE ... CASE for every line.
        $case = '';
        $bindings = [];
        foreach ($balances as $pid => $qty) {
            $case .= ' WHEN ? THEN ?';
            array_push($bindings, $pid, $qty);
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        DB::update(
            "UPDATE ".px('product_stocks')." SET qty = CASE product_id{$case} END, updated_at = ? WHERE branch_id = ? AND product_id IN ({$in})",
            array_merge($bindings, [$now, $branchId], $ids)
        );

        $rows = [];
        foreach ($deltas as $pid => $delta) {
            $rows[] = [
                'branch_id' => $branchId, 'product_id' => $pid, 'type' => $type,
                'qty' => $delta, 'balance_after' => $balances[$pid],
                'ref_type' => $refType, 'ref_id' => $refId, 'note' => $note,
                'user_id' => $userId, 'created_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('stock_movements')->insert($chunk);
        }

        return $balances;
    }
}
