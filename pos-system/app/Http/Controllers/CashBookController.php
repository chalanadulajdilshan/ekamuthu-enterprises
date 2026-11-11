<?php

namespace App\Http\Controllers;

use App\Models\CashEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Cash book for the current branch: every movement of physical cash, with a running balance.
 *
 * IN : cash bills (+ cash paid up-front on credit bills), customer cash payments, manual cash-in entries
 * OUT: voided cash bills (on the day they were voided), amounts paid on purchases (GRN),
 *      supplier cash payments, manual cash-out entries (expenses, bank deposits ...)
 *
 * Bills are summed per day (one row per day) so a busy day stays readable. Voids are
 * posted on the void date, so a closed day's balance never changes afterwards.
 */
class CashBookController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString();
        $from = $request->filled('from') ? Carbon::parse($request->from)->toDateString() : $today;
        $to = $request->filled('to') ? Carbon::parse($request->to)->toDateString() : $today;
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $branchId = app('currentBranch')->id;

        $opening = (float) DB::query()->fromSub($this->movements($branchId), 'x')
            ->where('d', '<', $from)
            ->selectRaw('COALESCE(SUM(cin), 0) - COALESCE(SUM(cout), 0) AS bal')->value('bal');

        $rows = DB::query()->fromSub($this->movements($branchId), 'x')
            ->whereBetween('d', [$from, $to])
            ->orderBy('d')->orderBy('t')->orderBy('ref_id')
            ->get();

        $balance = $opening;
        foreach ($rows as $r) {
            $balance = round($balance + (float) $r->cin - (float) $r->cout, 2);
            $r->balance = $balance;
        }

        return view('cash-book.index', [
            'range' => [$from, $to],
            'rows' => $rows,
            'opening' => $opening,
            'totalIn' => (float) $rows->sum('cin'),
            'totalOut' => (float) $rows->sum('cout'),
            'closing' => $balance,
            'inTypes' => CashEntry::typesFor('in'),
            'outTypes' => CashEntry::typesFor('out'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'direction' => 'required|in:in,out',
            'type' => 'required|in:'.implode(',', array_keys(CashEntry::TYPES)),
            'amount' => 'required|numeric|gt:0|max:999999999',
            'entry_date' => 'required|date|before_or_equal:today',
            'ref_no' => 'nullable|string|max:60',
            'note' => 'nullable|string|max:200',
        ]);
        abort_unless(CashEntry::TYPES[$data['type']][1] === $data['direction'], 422);

        CashEntry::create($data + [
            'branch_id' => app('currentBranch')->id,
            'user_id' => $request->user()->id,
            'entry_date' => Carbon::parse($data['entry_date'])->toDateString(),
        ]);

        return back()->with('ok', CashEntry::TYPES[$data['type']][0].' of '.money($data['amount']).' recorded.');
    }

    public function destroy(CashEntry $entry)
    {
        abort_unless((int) $entry->branch_id === (int) app('currentBranch')->id, 404);
        $entry->delete();

        return back()->with('ok', 'Cash book entry deleted.');
    }

    /**
     * UNION of every cash movement in the branch.
     * Columns: d (date), t (time, for ordering), kind, ref, ref_id, descr, cin, cout.
     */
    private function movements(int $branchId)
    {
        $cashOnBill = "CASE WHEN payment_method = 'cash' THEN total ELSE paid_amount END";

        $sales = DB::table('sales')
            ->where('branch_id', $branchId)
            ->whereRaw("(payment_method = 'cash' OR (payment_method = 'credit' AND paid_amount > 0))")
            ->groupBy('sale_date')
            ->selectRaw("sale_date AS d, MAX(created_at) AS t, 'sales' AS kind, NULL AS ref, 0 AS ref_id,
                CONCAT(COUNT(*), ' bill(s)') AS descr, SUM($cashOnBill) AS cin, 0 AS cout");

        $voids = DB::table('sales')
            ->where('branch_id', $branchId)->where('status', 'void')
            ->whereRaw("(payment_method = 'cash' OR (payment_method = 'credit' AND paid_amount > 0))")
            ->groupByRaw('DATE(updated_at)')
            ->selectRaw("DATE(updated_at) AS d, MAX(updated_at) AS t, 'void' AS kind, NULL AS ref, 0 AS ref_id,
                GROUP_CONCAT(bill_no ORDER BY id SEPARATOR ', ') AS descr, 0 AS cin, SUM($cashOnBill) AS cout");

        $custPay = DB::table('customer_payments as p')
            ->join('customers as c', 'c.id', '=', 'p.customer_id')
            ->where('p.branch_id', $branchId)->where('p.method', 'cash')
            ->selectRaw(str_replace('{p}', px('p'), "DATE({p}.created_at) AS d, {p}.created_at AS t, 'customer' AS kind, NULL AS ref,
                {p}.customer_id AS ref_id, CONCAT(".px('c').".name, COALESCE(CONCAT(' - ', {p}.note), '')) AS descr, {p}.amount AS cin, 0 AS cout"));

        $purchases = DB::table('purchases as p')
            ->join('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->where('p.branch_id', $branchId)->where('p.paid', '>', 0)
            ->selectRaw(str_replace('{p}', px('p'), "{p}.purchase_date AS d, {p}.created_at AS t, 'purchase' AS kind, {p}.ref_no AS ref,
                {p}.id AS ref_id, ".px('s').".name AS descr, 0 AS cin, {p}.paid AS cout"));

        $supPay = DB::table('supplier_payments as p')
            ->join('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->where('p.branch_id', $branchId)->where('p.method', 'cash')
            ->selectRaw(str_replace('{p}', px('p'), "DATE({p}.created_at) AS d, {p}.created_at AS t, 'supplier' AS kind, NULL AS ref,
                {p}.supplier_id AS ref_id, CONCAT(".px('s').".name, COALESCE(CONCAT(' - ', {p}.note), '')) AS descr, 0 AS cin, {p}.amount AS cout"));

        $manual = DB::table('cash_entries')
            ->where('branch_id', $branchId)
            ->selectRaw("entry_date AS d, created_at AS t, CONCAT('entry:', type) AS kind, ref_no AS ref, id AS ref_id,
                note AS descr, CASE WHEN direction = 'in' THEN amount ELSE 0 END AS cin,
                CASE WHEN direction = 'out' THEN amount ELSE 0 END AS cout");

        return $sales->unionAll($voids)->unionAll($custPay)->unionAll($purchases)->unionAll($supPay)->unionAll($manual);
    }
}
