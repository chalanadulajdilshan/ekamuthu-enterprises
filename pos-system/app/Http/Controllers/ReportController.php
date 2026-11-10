<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\SaleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * All report queries filter on sales(branch_id, status, sale_date) – a range scan
 * on an index – and aggregate in SQL, so they stay fast with millions of bills.
 */
class ReportController extends Controller
{
    private function range(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->toDateString() : now()->startOfMonth()->toDateString();
        $to = $request->filled('to') ? Carbon::parse($request->to)->toDateString() : now()->toDateString();

        return $from > $to ? [$to, $from] : [$from, $to];
    }

    private function base(array $range)
    {
        return DB::table('sales')
            ->where('branch_id', app('currentBranch')->id)
            ->where('status', 'completed')
            ->whereBetween('sale_date', $range);
    }

    public function sales(Request $request)
    {
        $range = $this->range($request);

        $summary = $this->base($range)->selectRaw('COUNT(*) AS bills, COALESCE(SUM(subtotal),0) AS gross,
            COALESCE(SUM(discount_amount),0) AS discount, COALESCE(SUM(tax_amount),0) AS tax,
            COALESCE(SUM(total),0) AS net, COALESCE(SUM(cost_total),0) AS cost')->first();

        $byMethod = $this->base($range)->groupBy('payment_method')
            ->selectRaw('payment_method, COUNT(*) AS bills, SUM(total) AS total')->get();

        $daily = $this->base($range)->groupBy('sale_date')->orderBy('sale_date')
            ->selectRaw('sale_date, COUNT(*) AS bills, SUM(total) AS total, SUM(total - tax_amount - cost_total) AS profit')->get();

        return view('reports.sales', compact('range', 'summary', 'byMethod', 'daily'));
    }

    public function bills(Request $request)
    {
        $range = $this->range($request);
        $q = DB::table('sales as s')
            ->leftJoin('customers as c', 'c.id', '=', 's.customer_id')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->where('s.branch_id', app('currentBranch')->id)
            ->whereBetween('s.sale_date', $range)
            ->select('s.id', 's.bill_no', 's.created_at', 's.item_count', 's.total', 's.payment_method', 's.status',
                'c.name as customer', 'u.name as cashier');

        if ($request->filled('status')) {
            $q->where('s.status', $request->status);
        }
        if ($request->filled('method')) { // input() – $request->method is the HTTP verb helper
            $q->where('s.payment_method', $request->input('method'));
        }
        if ($term = trim((string) $request->q)) {
            $q->where('s.bill_no', 'like', '%'.addcslashes($term, '%_').'%');
        }

        return view('reports.bills', ['range' => $range, 'bills' => $q->orderByDesc('s.id')->paginate(30)->withQueryString()]);
    }

    public function showBill(Sale $sale)
    {
        $sale->load(['items', 'customer:id,name,phone', 'user:id,name']);

        return view('reports.bill', compact('sale'));
    }

    public function voidBill(Request $request, Sale $sale)
    {
        abort_unless($request->user()->isAdmin(), 403);
        SaleService::void($sale, $request->user());

        return back()->with('ok', "Bill {$sale->bill_no} voided and stock returned.");
    }

    public function products(Request $request)
    {
        $range = $this->range($request);
        $sort = $request->input('sort') === 'profit' ? 'profit' : ($request->input('sort') === 'qty' ? 'qty' : 'revenue');

        $rows = DB::table('sale_items as i')
            ->joinSub($this->base($range)->select('id'), 's', 's.id', '=', 'i.sale_id')
            ->groupBy('i.product_id', 'i.name')
            ->selectRaw(str_replace('{i}', px('i'), '{i}.product_id, {i}.name, SUM({i}.qty) AS qty, SUM({i}.line_total) AS revenue,
                SUM({i}.line_total - {i}.qty * {i}.cost_price) AS profit'))
            ->orderByDesc($sort)
            ->paginate(30)->withQueryString();

        return view('reports.products', compact('range', 'rows', 'sort'));
    }
}
