<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\ProductSearch;
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

    /**
     * Out of stock / re-order report for the current branch.
     *  - show=out  : stock <= 0 (default)
     *  - show=low  : stock <= re-order level (includes out of stock)
     * Products with no product_stocks row count as 0 stock. Sales in the last 30 days
     * come from one range scan on sales_report_idx; last sold / last supplier are
     * read only for the rows on the page (or per chunk for the CSV export).
     */
    public function outOfStock(Request $request)
    {
        $branchId = app('currentBranch')->id;
        $show = $request->input('show') === 'low' ? 'low' : 'out';
        $sort = in_array($request->input('sort'), ['name', 'category', 'stock'], true) ? $request->input('sort') : 'demand';
        $since = now()->subDays(29)->toDateString();
        $stockSql = 'COALESCE('.px('s').'.qty, 0)';

        $sold30 = DB::table('sale_items as i')
            ->join('sales as sa', 'sa.id', '=', 'i.sale_id')
            ->where('sa.branch_id', $branchId)->where('sa.status', 'completed')->where('sa.sale_date', '>=', $since)
            ->groupBy('i.product_id')
            ->selectRaw('i.product_id, SUM('.px('i').'.qty) AS qty');

        $base = DB::table('products')
            ->join('categories as c', 'c.id', '=', 'products.category_id')
            ->leftJoin('product_stocks as s', fn ($j) => $j->on('s.product_id', '=', 'products.id')->where('s.branch_id', '=', $branchId))
            ->where('products.is_active', true);
        if ($request->filled('category')) {
            $base->where('products.category_id', (int) $request->input('category'));
        }
        ProductSearch::apply($base, (string) $request->input('q', ''));

        // Counters for the header cards, before the out/low filter is applied.
        $counts = (clone $base)->selectRaw(
            "SUM($stockSql <= 0) AS out_count, SUM($stockSql > 0 AND $stockSql <= ".px('products').'.reorder_level) AS low_count'
        )->first();

        $q = (clone $base)
            ->leftJoinSub($sold30, 'd', 'd.product_id', '=', 'products.id')
            ->select('products.id', 'products.code', 'products.barcode', 'products.name', 'products.unit',
                'products.cost_price', 'products.reorder_level', 'c.name as category',
                DB::raw("$stockSql AS stock"), DB::raw('COALESCE('.px('d').'.qty, 0) AS sold_30'));

        $show === 'out'
            ? $q->whereRaw("$stockSql <= 0")
            : $q->whereRaw("$stockSql <= ".px('products').'.reorder_level');

        match ($sort) {
            'name' => $q->orderBy('products.name'),
            'category' => $q->orderBy('c.name')->orderBy('products.name'),
            'stock' => $q->orderByRaw($stockSql)->orderBy('products.name'),
            default => $q->orderByDesc('sold_30')->orderBy('products.name'),
        };
        $q->orderBy('products.id');

        if ($request->input('export') === 'csv') {
            return $this->outOfStockCsv($q, $branchId, $show);
        }

        $rows = $q->paginate(50)->withQueryString();
        $this->enrichStockRows($rows->getCollection(), $branchId);

        return view('reports.out-of-stock', [
            'rows' => $rows, 'show' => $show, 'sort' => $sort, 'counts' => $counts,
            'categories' => \App\Models\Category::activeCached(),
        ]);
    }

    /** Adds last sold date, last supplier / cost and a suggested order qty to each row. */
    private function enrichStockRows($rows, int $branchId): void
    {
        $ids = $rows->pluck('id')->all();
        if (! $ids) {
            return;
        }

        $lastSold = DB::table('sale_items as i')
            ->join('sales as sa', 'sa.id', '=', 'i.sale_id')
            ->where('sa.branch_id', $branchId)->where('sa.status', 'completed')
            ->whereIn('i.product_id', $ids)
            ->groupBy('i.product_id')
            ->selectRaw('i.product_id, MAX('.px('sa').'.sale_date) AS d')
            ->pluck('d', 'product_id');

        $lastItem = DB::table('purchase_items as pi')
            ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
            ->where('p.branch_id', $branchId)
            ->whereIn('pi.product_id', $ids)
            ->groupBy('pi.product_id')
            ->selectRaw('MAX('.px('pi').'.id) AS id');
        $lastBuy = DB::table('purchase_items as pi')
            ->joinSub($lastItem, 'x', 'x.id', '=', 'pi.id')
            ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
            ->join('suppliers as su', 'su.id', '=', 'p.supplier_id')
            ->get(['pi.product_id', 'pi.unit_cost', 'p.purchase_date', 'su.name as supplier', 'su.phone as supplier_phone'])
            ->keyBy('product_id');

        $rows->each(function ($r) use ($lastSold, $lastBuy) {
            $b = $lastBuy[$r->id] ?? null;
            $r->last_sold = $lastSold[$r->id] ?? null;
            $r->supplier = $b->supplier ?? null;
            $r->supplier_phone = $b->supplier_phone ?? null;
            $r->last_cost = $b ? (float) $b->unit_cost : (float) $r->cost_price;
            $r->last_purchase = $b->purchase_date ?? null;
            // Enough to cover ~30 days of sales, and at least twice the re-order level.
            $target = max((float) $r->sold_30, 2 * (float) $r->reorder_level);
            $r->suggested = max(ceil($target - (float) $r->stock), 0);
        });
    }

    private function outOfStockCsv($q, int $branchId, string $show)
    {
        $file = ($show === 'out' ? 'out-of-stock' : 'reorder-list').'-'.app('currentBranch')->code.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($q, $branchId) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Sinhala names correctly
            fputcsv($out, ['Code', 'Barcode', 'Product', 'Category', 'Unit', 'Stock', 'Re-order level', 'Sold (30 days)',
                'Last sold', 'Last supplier', 'Supplier phone', 'Last cost', 'Suggested order qty', 'Order value']);
            $page = 1;
            do {
                $chunk = (clone $q)->forPage($page++, 500)->get();
                $this->enrichStockRows($chunk, $branchId);
                foreach ($chunk as $r) {
                    fputcsv($out, [$r->code, $r->barcode, $r->name, $r->category, $r->unit, (float) $r->stock,
                        (float) $r->reorder_level, (float) $r->sold_30, $r->last_sold, $r->supplier, $r->supplier_phone,
                        $r->last_cost, $r->suggested, round($r->suggested * $r->last_cost, 2)]);
                }
            } while ($chunk->count() === 500);
            fclose($out);
        }, $file, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
