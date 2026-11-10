<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductSearch;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $branchId = app('currentBranch')->id;

        // Filters on products only -> index-only COUNT, then a deferred join for
        // the 25 rows shown (same technique as the POS grid; see PosController::products).
        $base = DB::table('products');
        if ($request->filled('category')) {
            $base->where('products.category_id', (int) $request->category);
        }
        if ($request->input('status') === 'inactive') {
            $base->where('products.is_active', false);
        } elseif ($request->input('status') !== 'all') {
            $base->where('products.is_active', true);
        }
        ProductSearch::apply($base, (string) $request->input('q', ''));

        $perPage = 25;
        $total = (clone $base)->count();
        $page = min(max((int) $request->input('page', 1), 1), max((int) ceil($total / $perPage), 1));
        $ids = (clone $base)->orderBy('products.name')->orderBy('products.id')
            ->forPage($page, $perPage)->pluck('products.id')->all();

        $rows = $ids ? DB::table('products')
            ->join('categories as c', 'c.id', '=', 'products.category_id')
            ->leftJoin('product_stocks as s', fn ($j) => $j->on('s.product_id', '=', 'products.id')->where('s.branch_id', '=', $branchId))
            ->whereIn('products.id', $ids)
            ->get(['products.id', 'products.code', 'products.barcode', 'products.name', 'products.unit',
                'products.cost_price', 'products.sell_price', 'products.is_active', 'products.image',
                'c.name as category', DB::raw('COALESCE('.px('s').'.qty, 0) AS stock')])
            ->keyBy('id') : collect();

        $items = collect($ids)->filter(fn ($id) => isset($rows[$id]))->map(fn ($id) => $rows[$id])->values();
        $products = (new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => $request->url(), 'pageName' => 'page',
        ]))->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => Category::orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return view('products.form', ['product' => new Product(['is_active' => true, 'unit' => 'pcs']), 'categories' => Category::orderBy('name')->get(['id', 'name'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $opening = (float) $request->input('opening_stock', 0);

        DB::transaction(function () use ($data, $opening, $request) {
            $product = Product::create($data);
            if ($opening != 0) {
                StockService::apply(app('currentBranch')->id, [$product->id => $opening], 'opening', 'product', $product->id, $request->user()->id, 'Opening stock');
            }
        });

        return redirect()->route('products.index')->with('ok', 'Product created.');
    }

    public function edit(Product $product)
    {
        return view('products.form', ['product' => $product, 'categories' => Category::orderBy('name')->get(['id', 'name'])]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product);
        if ($request->hasFile('image') && $product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->update($data);

        return redirect()->route('products.index')->with('ok', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $used = DB::table('sale_items')->where('product_id', $product->id)->exists()
            || DB::table('purchase_items')->where('product_id', $product->id)->exists();

        if ($used) {
            $product->update(['is_active' => false]);

            return back()->with('ok', 'Product has history, so it was deactivated instead of deleted.');
        }

        DB::transaction(function () use ($product) {
            DB::table('stock_movements')->where('product_id', $product->id)->delete();
            $product->delete();
        });
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        return back()->with('ok', 'Product deleted.');
    }

    /** Lightweight JSON search used by the purchase form. */
    public function search(Request $request)
    {
        $q = DB::table('products')->where('is_active', true)
            ->select('id', 'code', 'name', 'unit', 'cost_price', 'sell_price');
        ProductSearch::apply($q, (string) $request->input('q', ''));

        return response()->json($q->orderBy('name')->limit(15)->get());
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('products')->ignore($product)],
            'barcode' => ['nullable', 'string', 'max:64', Rule::unique('products')->ignore($product)],
            'name' => 'required|string|max:150',
            'category_id' => 'required|exists:categories,id',
            'unit' => 'required|string|max:15',
            'cost_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'opening_stock' => 'nullable|numeric',
        ]);
        unset($data['opening_stock']);
        $data['is_active'] = $request->boolean('is_active');
        $data['reorder_level'] = $data['reorder_level'] ?? 0;
        $data['barcode'] = $data['barcode'] ?: null;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        } else {
            unset($data['image']);
        }

        return $data;
    }
}
