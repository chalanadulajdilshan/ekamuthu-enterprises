<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HeldSale;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Services\DocNumber;
use App\Services\ProductSearch;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index()
    {
        $branch = app('currentBranch');

        return view('pos.index', [
            'categories' => Category::activeCached(),
            'nextBill' => DocNumber::peek('sale:'.$branch->id, $branch->code),
        ]);
    }

    /**
     * Product grid feed - constant cost no matter how big the catalogue gets:
     *
     *  1. COUNT runs on `products` alone (no joins): an index-only count on
     *     (is_active, category_id). The joins can never change the count - category
     *     is a restricted FK and stock is a LEFT JOIN - so joining them for the count
     *     would only add one lookup per product.
     *  2. "Deferred join": page through ids on the (is_active[, category_id], name)
     *     index, so OFFSET skips cheap index entries, not joined rows.
     *  3. Join category + branch stock for just the 10 ids being shown.
     */
    public function products(Request $request)
    {
        $branchId = app('currentBranch')->id;
        $perPage = min(max((int) $request->input('per_page', Setting::val('products_per_page', 10)), 5), 60);

        $base = DB::table('products')->where('products.is_active', true);
        if ($cat = (int) $request->input('category')) {
            $base->where('products.category_id', $cat);
        }
        $term = trim((string) $request->input('q', ''));
        ProductSearch::apply($base, $term);

        // Browsing (no search) is the most common request and its count only changes
        // when products are added/removed/(de)activated -> cache it per category.
        $total = $term === ''
            ? Cache::remember(Product::countCacheKey('pos', $cat), 600, fn () => (clone $base)->count())
            : (clone $base)->count();
        $lastPage = max((int) ceil($total / $perPage), 1);
        $pageNo = min(max((int) $request->input('page', 1), 1), $lastPage);

        // Tie-break on id so equal names never shuffle between pages (InnoDB secondary
        // indexes already end with the PK, so this ordering is still index-only).
        $ids = (clone $base)->orderBy('products.name')->orderBy('products.id')
            ->forPage($pageNo, $perPage)->pluck('products.id')->all();

        $rows = $ids ? DB::table('products')
            ->join('categories as c', 'c.id', '=', 'products.category_id')
            ->leftJoin('product_stocks as s', function ($j) use ($branchId) {
                $j->on('s.product_id', '=', 'products.id')->where('s.branch_id', '=', $branchId);
            })
            ->whereIn('products.id', $ids)
            ->get(['products.id', 'products.code', 'products.name', 'products.unit', 'products.sell_price',
                'products.image', 'products.reorder_level', 'c.icon', DB::raw('COALESCE('.px('s').'.qty, 0) AS stock')])
            ->keyBy('id') : collect();

        return response()->json([
            'data' => collect($ids)->filter(fn ($id) => isset($rows[$id]))->map(function ($id) use ($rows) {
                $p = $rows[$id];

                return [
                    'id' => $p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'unit' => $p->unit,
                    'price' => (float) $p->sell_price,
                    'stock' => (float) $p->stock,
                    'low' => (float) $p->stock <= (float) $p->reorder_level,
                    'icon' => $p->icon,
                    'image' => $p->image ? asset('uploads/'.$p->image) : null,
                ];
            })->values(),
            'page' => $pageNo,
            'last_page' => $lastPage,
            'total' => $total,
        ]);
    }

    /** Barcode scanner / exact code lookup – one unique-index read. */
    public function lookup(Request $request)
    {
        $id = ProductSearch::exactId((string) $request->input('code', ''));
        if (! $id) {
            return response()->json(['found' => false], 404);
        }
        $branchId = app('currentBranch')->id;
        $p = DB::table('products')
            ->leftJoin('product_stocks as s', fn ($j) => $j->on('s.product_id', '=', 'products.id')->where('s.branch_id', '=', $branchId))
            ->where('products.id', $id)->where('products.is_active', true)
            ->first(['products.id', 'products.code', 'products.name', 'products.unit', 'products.sell_price', DB::raw('COALESCE('.px('s').'.qty, 0) AS stock')]);

        if (! $p) {
            return response()->json(['found' => false], 404);
        }

        return response()->json(['found' => true, 'product' => [
            'id' => $p->id, 'code' => $p->code, 'name' => $p->name, 'unit' => $p->unit,
            'price' => (float) $p->sell_price, 'stock' => (float) $p->stock,
        ]]);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1|max:500',
            'items.*.product_id' => 'required|integer',
            'items.*.qty' => 'required|numeric|gt:0|max:999999',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'discount_type' => 'required|in:percent,amount',
            'discount_value' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,card,bank,credit',
            'paid_amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
            'held_id' => 'nullable|integer',
        ]);

        $branch = app('currentBranch');
        $sale = SaleService::checkout($data, $request->user(), $branch);

        if (! empty($data['held_id'])) {
            HeldSale::where('id', $data['held_id'])->where('branch_id', $branch->id)->delete();
        }

        return response()->json([
            'ok' => true,
            'sale_id' => $sale->id,
            'bill_no' => $sale->bill_no,
            'total' => (float) $sale->total,
            'change' => (float) $sale->change_amount,
            'receipt_url' => route('pos.receipt', $sale),
            'next_bill' => DocNumber::peek('sale:'.$branch->id, $branch->code),
        ]);
    }

    public function receipt(Sale $sale)
    {
        $sale->load(['items', 'customer:id,name,phone', 'user:id,name', 'branch']);

        return view('pos.receipt', ['sale' => $sale, 'autoPrint' => request()->boolean('print', true)]);
    }

    public function lastReceipt()
    {
        $id = Sale::where('branch_id', app('currentBranch')->id)->where('user_id', auth()->id())->max('id');
        abort_unless($id, 404, 'No bills yet.');

        return redirect()->route('pos.receipt', $id);
    }

    // ---- Hold / recall -------------------------------------------------------

    public function hold(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.qty' => 'required|numeric|gt:0',
            'customer_id' => 'nullable|integer',
            'customer_name' => 'nullable|string|max:120',
            'discount_type' => 'nullable|in:percent,amount',
            'discount_value' => 'nullable|numeric',
            'label' => 'nullable|string|max:80',
            'total' => 'nullable|numeric',
            'held_id' => 'nullable|integer',
        ]);

        // Re-holding a recalled cart replaces the old hold instead of duplicating it.
        if (! empty($data['held_id'])) {
            HeldSale::where('id', $data['held_id'])->where('branch_id', app('currentBranch')->id)->delete();
        }

        $held = HeldSale::create([
            'branch_id' => app('currentBranch')->id,
            'user_id' => $request->user()->id,
            'customer_id' => $data['customer_id'] ?? null,
            'label' => $data['label'] ?? null,
            'payload' => $request->only(['items', 'customer_id', 'customer_name', 'discount_type', 'discount_value']),
            'item_count' => count($data['items']),
            'total' => $data['total'] ?? 0,
        ]);

        return response()->json(['ok' => true, 'id' => $held->id]);
    }

    public function heldList()
    {
        return response()->json(
            HeldSale::where('branch_id', app('currentBranch')->id)->orderByDesc('id')->limit(50)
                ->get(['id', 'label', 'item_count', 'total', 'created_at', 'payload'])
                ->map(fn ($h) => [
                    'id' => $h->id, 'label' => $h->label, 'item_count' => $h->item_count,
                    'total' => (float) $h->total, 'customer' => $h->payload['customer_name'] ?? 'Walk-in Customer',
                    'time' => $h->created_at->format('d M h:i A'),
                ])
        );
    }

    /** Returns a held cart with *current* prices and stock re-read from the DB. */
    public function heldRestore(HeldSale $held)
    {
        abort_unless($held->branch_id === app('currentBranch')->id, 404);
        $payload = $held->payload;
        $qty = collect($payload['items'])->pluck('qty', 'id');
        $branchId = $held->branch_id;

        $items = DB::table('products')
            ->leftJoin('product_stocks as s', fn ($j) => $j->on('s.product_id', '=', 'products.id')->where('s.branch_id', '=', $branchId))
            ->whereIn('products.id', $qty->keys())->where('products.is_active', true)
            ->get(['products.id', 'products.code', 'products.name', 'products.unit', 'products.sell_price', DB::raw('COALESCE('.px('s').'.qty, 0) AS stock')])
            ->map(fn ($p) => [
                'id' => $p->id, 'code' => $p->code, 'name' => $p->name, 'unit' => $p->unit,
                'price' => (float) $p->sell_price, 'stock' => (float) $p->stock, 'qty' => (float) $qty[$p->id],
            ])->values();

        return response()->json([
            'id' => $held->id,
            'items' => $items,
            'customer_id' => $payload['customer_id'] ?? null,
            'customer_name' => $payload['customer_name'] ?? null,
            'discount_type' => $payload['discount_type'] ?? 'percent',
            'discount_value' => (float) ($payload['discount_value'] ?? 0),
        ]);
    }

    public function heldDelete(HeldSale $held)
    {
        abort_unless($held->branch_id === app('currentBranch')->id, 404);
        $held->delete();

        return response()->json(['ok' => true]);
    }
}
