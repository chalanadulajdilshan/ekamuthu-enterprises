<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    /**
     * Totals are always recomputed on the server from DB prices –
     * the browser only sends product ids and quantities.
     */
    public static function calculate(array $lines, string $discountType, float $discountValue, float $taxRate): array
    {
        $subtotal = 0.0;
        foreach ($lines as $l) {
            $subtotal += round($l['qty'] * $l['unit_price'], 2);
        }
        $subtotal = round($subtotal, 2);

        $discount = $discountType === 'percent'
            ? round($subtotal * min(max($discountValue, 0), 100) / 100, 2)
            : round(min(max($discountValue, 0), $subtotal), 2);

        $taxable = $subtotal - $discount;
        $tax = round($taxable * $taxRate / 100, 2);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'total' => round($taxable + $tax, 2),
        ];
    }

    public static function checkout(array $data, User $user, Branch $branch): Sale
    {
        // Merge duplicate lines of the same product.
        $qtys = [];
        foreach ($data['items'] as $it) {
            $qtys[(int) $it['product_id']] = ($qtys[(int) $it['product_id']] ?? 0) + (float) $it['qty'];
        }

        $products = DB::table('products')->whereIn('id', array_keys($qtys))
            ->where('is_active', true)
            ->get(['id', 'name', 'sell_price', 'cost_price'])->keyBy('id');

        if ($products->count() !== count($qtys)) {
            throw ValidationException::withMessages(['items' => 'One or more products are no longer available.']);
        }

        $lines = [];
        foreach ($qtys as $pid => $qty) {
            $p = $products[$pid];
            $lines[] = [
                'product_id' => $pid, 'name' => $p->name, 'qty' => round($qty, 3),
                'unit_price' => (float) $p->sell_price, 'cost_price' => (float) $p->cost_price,
            ];
        }

        $taxRate = (float) Setting::val('tax_rate', 0);
        $discountType = $data['discount_type'] ?? 'amount';
        $t = self::calculate($lines, $discountType, (float) ($data['discount_value'] ?? 0), $taxRate);

        $method = $data['payment_method'];
        $customerId = $data['customer_id'] ?? null;
        $paid = round((float) ($data['paid_amount'] ?? 0), 2);

        if ($method === 'cash' && $paid + 0.001 < $t['total']) {
            throw ValidationException::withMessages(['paid_amount' => 'Cash received is less than the total payable.']);
        }
        if (in_array($method, ['card', 'bank'], true)) {
            $paid = $t['total'];
        }
        if ($method === 'credit') {
            if (! $customerId) {
                throw ValidationException::withMessages(['customer_id' => 'Select a customer for a credit sale.']);
            }
            $paid = min(max($paid, 0), $t['total']);
        }
        $change = $method === 'cash' ? round($paid - $t['total'], 2) : 0;
        $recordedPaid = $method === 'cash' ? $t['total'] : $paid;
        $due = round($t['total'] - $recordedPaid, 2);

        return DB::transaction(function () use ($lines, $t, $data, $discountType, $taxRate, $method, $customerId, $paid, $change, $due, $user, $branch) {
            if ($due > 0) {
                $c = Customer::query()->whereKey($customerId)->lockForUpdate()->firstOrFail(['id', 'balance', 'credit_limit']);
                if ($c->credit_limit > 0 && $c->balance + $due > $c->credit_limit) {
                    throw ValidationException::withMessages(['customer_id' => 'Credit limit exceeded. Outstanding: '.number_format($c->balance, 2)]);
                }
            }

            $now = now();
            $sale = Sale::create([
                'bill_no' => DocNumber::next('sale:'.$branch->id, $branch->code),
                'branch_id' => $branch->id,
                'user_id' => $user->id,
                'customer_id' => $customerId,
                'item_count' => count($lines),
                'subtotal' => $t['subtotal'],
                'discount_type' => $discountType,
                'discount_value' => (float) ($data['discount_value'] ?? 0),
                'discount_amount' => $t['discount_amount'],
                'tax_rate' => $taxRate,
                'tax_amount' => $t['tax_amount'],
                'total' => $t['total'],
                'cost_total' => round(array_sum(array_map(fn ($l) => $l['qty'] * $l['cost_price'], $lines)), 2),
                'payment_method' => $method,
                'paid_amount' => $paid,
                'change_amount' => $change,
                'status' => 'completed',
                'note' => $data['note'] ?? null,
                'sale_date' => $now->toDateString(),
            ]);

            DB::table('sale_items')->insert(array_map(fn ($l) => $l + [
                'sale_id' => $sale->id,
                'line_total' => round($l['qty'] * $l['unit_price'], 2),
            ], $lines));

            $deltas = [];
            foreach ($lines as $l) {
                $deltas[$l['product_id']] = -$l['qty'];
            }
            StockService::apply(
                $branch->id, $deltas, 'sale', 'sale', $sale->id, $user->id, $sale->bill_no,
                preventNegative: ! Setting::val('allow_negative_stock'),
            );

            if ($due > 0) {
                DB::table('customers')->where('id', $customerId)->increment('balance', $due);
            }

            return $sale;
        });
    }

    public static function void(Sale $sale, User $user): void
    {
        if ($sale->status === 'void') {
            return;
        }

        DB::transaction(function () use ($sale, $user) {
            $sale = Sale::query()->whereKey($sale->id)->lockForUpdate()->first();
            if ($sale->status === 'void') {
                return;
            }

            $deltas = DB::table('sale_items')->where('sale_id', $sale->id)
                ->selectRaw('product_id, SUM(qty) AS qty')->groupBy('product_id')
                ->pluck('qty', 'product_id')->map(fn ($q) => (float) $q)->all();

            StockService::apply($sale->branch_id, $deltas, 'void', 'sale', $sale->id, $user->id, 'Void '.$sale->bill_no);

            $paidOnBill = $sale->payment_method === 'cash' ? $sale->total : $sale->paid_amount;
            $due = round($sale->total - $paidOnBill, 2);
            if ($sale->customer_id && $due > 0) {
                DB::table('customers')->where('id', $sale->customer_id)->decrement('balance', $due);
            }

            $sale->update(['status' => 'void']);
        });
    }
}
