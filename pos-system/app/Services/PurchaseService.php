<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    /**
     * Records a GRN / purchase: adds stock, updates weighted-average cost,
     * optionally updates the selling price and posts the supplier balance.
     */
    public static function create(array $data, User $user, Branch $branch): Purchase
    {
        return DB::transaction(function () use ($data, $user, $branch) {
            $lines = [];
            $subtotal = 0.0;
            foreach ($data['items'] as $it) {
                $qty = round((float) $it['qty'], 3);
                $cost = round((float) $it['unit_cost'], 2);
                $line = round($qty * $cost, 2);
                $subtotal += $line;
                $lines[] = [
                    'product_id' => (int) $it['product_id'], 'qty' => $qty, 'unit_cost' => $cost,
                    'line_total' => $line, 'sell_price' => $it['sell_price'] ?? null,
                ];
            }
            $subtotal = round($subtotal, 2);
            $discount = round(min((float) ($data['discount'] ?? 0), $subtotal), 2);
            $total = round($subtotal - $discount, 2);
            $paid = round(min((float) ($data['paid'] ?? 0), $total), 2);

            $purchase = Purchase::create([
                'ref_no' => DocNumber::next('purchase:'.$branch->id, 'GRN-'.$branch->code),
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $branch->id,
                'user_id' => $user->id,
                'purchase_date' => $data['purchase_date'],
                'invoice_no' => $data['invoice_no'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid' => $paid,
                'note' => $data['note'] ?? null,
            ]);

            DB::table('purchase_items')->insert(array_map(fn ($l) => [
                'purchase_id' => $purchase->id, 'product_id' => $l['product_id'], 'qty' => $l['qty'],
                'unit_cost' => $l['unit_cost'], 'line_total' => $l['line_total'],
            ], $lines));

            // Weighted average cost uses stock across all branches, read before the stock update.
            $ids = array_column($lines, 'product_id');
            $onHand = DB::table('product_stocks')->whereIn('product_id', $ids)
                ->groupBy('product_id')->selectRaw('product_id, SUM(qty) AS q')->pluck('q', 'product_id');
            $products = DB::table('products')->whereIn('id', $ids)->lockForUpdate()->get(['id', 'cost_price'])->keyBy('id');

            $deltas = [];
            foreach ($lines as $l) {
                $pid = $l['product_id'];
                $deltas[$pid] = ($deltas[$pid] ?? 0) + $l['qty'];

                $oldQty = max((float) ($onHand[$pid] ?? 0), 0);
                $oldCost = (float) $products[$pid]->cost_price;
                $newCost = $oldQty + $l['qty'] > 0
                    ? round(($oldQty * $oldCost + $l['qty'] * $l['unit_cost']) / ($oldQty + $l['qty']), 2)
                    : $l['unit_cost'];
                $onHand[$pid] = $oldQty + $l['qty'];
                $products[$pid]->cost_price = $newCost;

                $upd = ['cost_price' => $newCost, 'updated_at' => now()];
                if ($l['sell_price'] !== null && $l['sell_price'] !== '') {
                    $upd['sell_price'] = round((float) $l['sell_price'], 2);
                }
                DB::table('products')->where('id', $pid)->update($upd);
            }

            StockService::apply($branch->id, $deltas, 'purchase', 'purchase', $purchase->id, $user->id, $purchase->ref_no);

            if ($total - $paid > 0) {
                DB::table('suppliers')->where('id', $data['supplier_id'])->increment('balance', round($total - $paid, 2));
            }

            return $purchase;
        });
    }
}
