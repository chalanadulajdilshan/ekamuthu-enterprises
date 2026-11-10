<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductSearch;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $branchId = app('currentBranch')->id;

        $q = DB::table('products')
            ->join('categories as c', 'c.id', '=', 'products.category_id')
            ->leftJoin('product_stocks as s', fn ($j) => $j->on('s.product_id', '=', 'products.id')->where('s.branch_id', '=', $branchId))
            ->where('products.is_active', true)
            ->select('products.id', 'products.code', 'products.name', 'products.unit', 'products.cost_price',
                'products.sell_price', 'products.reorder_level', 'c.name as category', DB::raw('COALESCE('.px('s').'.qty, 0) AS stock'));

        if ($request->filled('category')) {
            $q->where('products.category_id', (int) $request->category);
        }
        match ($request->input('filter')) {
            'low' => $q->whereRaw('COALESCE('.px('s').'.qty, 0) <= '.px('products').'.reorder_level'),
            'out' => $q->whereRaw('COALESCE('.px('s').'.qty, 0) <= 0'),
            default => null,
        };
        ProductSearch::apply($q, (string) $request->input('q', ''));

        // Summary in ONE aggregate query over the branch stock table.
        $summary = DB::table('product_stocks as s')
            ->join('products as p', 'p.id', '=', 's.product_id')
            ->where('s.branch_id', $branchId)->where('p.is_active', true)
            ->selectRaw(strtr('COUNT(*) AS lines_, SUM(CASE WHEN {s}.qty > 0 THEN {s}.qty * {p}.cost_price ELSE 0 END) AS cost_value,
                SUM(CASE WHEN {s}.qty > 0 THEN {s}.qty * {p}.sell_price ELSE 0 END) AS retail_value,
                SUM({s}.qty <= {p}.reorder_level) AS low_count, SUM({s}.qty <= 0) AS out_count', ['{s}' => px('s'), '{p}' => px('p')]))
            ->first();

        return view('inventory.index', [
            'items' => $q->orderBy('products.name')->paginate(25)->withQueryString(),
            'categories' => Category::activeCached(),
            'summary' => $summary,
        ]);
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'mode' => 'required|in:add,remove,set',
            'qty' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:200',
        ]);
        $branchId = app('currentBranch')->id;

        DB::transaction(function () use ($data, $branchId, $request) {
            $current = (float) DB::table('product_stocks')->where('branch_id', $branchId)
                ->where('product_id', $data['product_id'])->lockForUpdate()->value('qty');
            $delta = match ($data['mode']) {
                'add' => (float) $data['qty'],
                'remove' => -(float) $data['qty'],
                'set' => (float) $data['qty'] - $current,
            };
            StockService::apply($branchId, [(int) $data['product_id'] => $delta], 'adjust', 'adjust', null,
                $request->user()->id, $data['note'] ?: 'Manual adjustment');
        });

        return back()->with('ok', 'Stock updated.');
    }

    public function movements(Product $product)
    {
        $branchId = app('currentBranch')->id;

        return view('inventory.movements', [
            'product' => $product,
            'stock' => (float) DB::table('product_stocks')->where('branch_id', $branchId)->where('product_id', $product->id)->value('qty'),
            // (product_id, branch_id, id) index -> newest first, no filesort
            'movements' => DB::table('stock_movements as m')
                ->leftJoin('users as u', 'u.id', '=', 'm.user_id')
                ->where('m.product_id', $product->id)->where('m.branch_id', $branchId)
                ->orderByDesc('m.id')
                ->select('m.*', 'u.name as user_name')
                ->paginate(30),
        ]);
    }
}
