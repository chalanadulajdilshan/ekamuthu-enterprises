<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductSearch;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Stock In" scan page for staff (phone or PC):
 * scan a barcode -> pick category + product (or create one) -> enter qty -> stock is added
 * to the current branch and logged in the stock ledger as type "stock_in".
 */
class StockInController extends Controller
{
    public function index(Request $request)
    {
        return view('stock-in.index', [
            'categories' => Category::activeCached(),
            'recent' => $this->recent($request),
        ]);
    }

    /** Barcode / code -> product with its category and current branch stock. */
    public function lookup(Request $request)
    {
        $code = trim((string) $request->input('code', ''));
        $id = ProductSearch::exactId($code);
        $p = $id ? $this->productRow($id) : null;

        return response()->json(['found' => (bool) $p, 'code' => $code, 'product' => $p]);
    }

    /** Products of a category (optionally filtered) for the picker. */
    public function products(Request $request)
    {
        $branchId = app('currentBranch')->id;
        $q = DB::table('products')
            ->leftJoin('product_stocks as s', fn ($j) => $j->on('s.product_id', '=', 'products.id')->where('s.branch_id', '=', $branchId))
            ->where('products.is_active', true)
            ->select('products.id', 'products.code', 'products.barcode', 'products.name', 'products.unit', 'products.category_id',
                DB::raw('COALESCE('.px('s').'.qty, 0) AS stock'));

        if ($request->filled('category')) {
            $q->where('products.category_id', (int) $request->category);
        }
        ProductSearch::apply($q, (string) $request->input('q', ''));

        return response()->json(['products' => $q->orderBy('products.name')->limit(50)->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'barcode' => 'nullable|string|max:64',
            'product_id' => 'nullable|integer|exists:products,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'new_category' => 'nullable|string|max:80',
            'new_product' => 'nullable|array',
            'new_product.name' => 'required_with:new_product|string|max:150',
            'new_product.unit' => 'nullable|string|max:15',
            'new_product.cost_price' => 'nullable|numeric|min:0',
            'new_product.sell_price' => 'required_with:new_product|numeric|min:0',
            'qty' => 'required|numeric|gt:0|max:999999',
            'note' => 'nullable|string|max:150',
        ]);
        $barcode = trim((string) ($data['barcode'] ?? '')) ?: null;
        $branchId = app('currentBranch')->id;
        $user = $request->user();
        $linked = false;

        if (empty($data['product_id']) && empty($data['new_product'])) {
            throw ValidationException::withMessages(['product_id' => 'Select a product or add a new one.']);
        }

        $result = DB::transaction(function () use ($data, $barcode, $branchId, $user, &$linked) {
            if (! empty($data['product_id'])) {
                $product = Product::lockForUpdate()->findOrFail($data['product_id']);

                // First time this barcode is seen: remember it on the product (if it has none yet).
                if ($barcode && ! $product->barcode && ! ProductSearch::exactId($barcode)) {
                    $product->update(['barcode' => $barcode]);
                    $linked = true;
                }
            } else {
                $categoryId = $this->resolveCategory($data);
                if ($barcode && ProductSearch::exactId($barcode)) {
                    throw ValidationException::withMessages(['barcode' => "Barcode {$barcode} already belongs to another product."]);
                }
                $np = $data['new_product'];
                $product = Product::create([
                    'code' => $this->newCode($barcode),
                    'barcode' => $barcode,
                    'name' => trim($np['name']),
                    'category_id' => $categoryId,
                    'unit' => trim($np['unit'] ?? '') ?: 'pcs',
                    'cost_price' => $np['cost_price'] ?? 0,
                    'sell_price' => $np['sell_price'],
                    'reorder_level' => 0,
                    'is_active' => true,
                ]);
            }

            $balance = StockService::apply($branchId, [$product->id => (float) $data['qty']], 'stock_in', 'stock_in', null,
                $user->id, ($data['note'] ?? '') ?: 'Stock in (scan)');

            return [$product, $balance[$product->id] ?? null];
        });
        [$product, $balance] = $result;

        return response()->json([
            'ok' => true,
            'message' => "Added {$this->fmt($data['qty'])} {$product->unit} to {$product->name}".($linked ? ' (barcode saved to product)' : '').'.',
            'product' => $this->productRow($product->id),
            'balance' => $balance,
            'recent' => $this->recent($request),
        ]);
    }

    private function resolveCategory(array $data): int
    {
        $name = trim((string) ($data['new_category'] ?? ''));
        if ($name !== '') {
            $cat = Category::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first()
                ?? Category::create(['name' => $name, 'is_active' => true, 'sort_order' => 0]);

            return $cat->id;
        }
        if (empty($data['category_id'])) {
            throw ValidationException::withMessages(['category_id' => 'Select a category for the new product.']);
        }

        return (int) $data['category_id'];
    }

    /** Use the barcode as the item code when it's free, otherwise SI-000123 style. */
    private function newCode(?string $barcode): string
    {
        if ($barcode && mb_strlen($barcode) <= 40 && ! DB::table('products')->where('code', $barcode)->exists()) {
            return $barcode;
        }
        $n = (int) DB::table('products')->max('id') + 1;
        do {
            $code = 'SI-'.str_pad((string) $n++, 6, '0', STR_PAD_LEFT);
        } while (DB::table('products')->where('code', $code)->exists());

        return $code;
    }

    private function productRow(int $id): ?array
    {
        $branchId = app('currentBranch')->id;
        $p = DB::table('products')
            ->join('categories as c', 'c.id', '=', 'products.category_id')
            ->leftJoin('product_stocks as s', fn ($j) => $j->on('s.product_id', '=', 'products.id')->where('s.branch_id', '=', $branchId))
            ->where('products.id', $id)->where('products.is_active', true)
            ->first(['products.id', 'products.code', 'products.barcode', 'products.name', 'products.unit',
                'products.category_id', 'c.name as category', DB::raw('COALESCE('.px('s').'.qty, 0) AS stock')]);

        return $p ? array_merge((array) $p, ['stock' => (float) $p->stock]) : null;
    }

    /** Today's stock-in entries by this user at this branch (newest first). */
    private function recent(Request $request)
    {
        return DB::table('stock_movements as m')
            ->join('products as p', 'p.id', '=', 'm.product_id')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->where('m.branch_id', app('currentBranch')->id)
            ->where('m.type', 'stock_in')
            ->where('m.user_id', $request->user()->id)
            ->where('m.created_at', '>=', today())
            ->orderByDesc('m.id')->limit(20)
            ->get(['m.id', 'm.qty', 'm.balance_after', 'm.created_at', 'p.name', 'p.code', 'p.unit', 'c.name as category'])
            ->map(fn ($r) => [
                'name' => $r->name, 'code' => $r->code, 'unit' => $r->unit, 'category' => $r->category,
                'qty' => (float) $r->qty, 'balance' => (float) $r->balance_after,
                'time' => \Carbon\Carbon::parse($r->created_at)->format('h:i A'),
            ]);
    }

    private function fmt($n): string
    {
        return rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.');
    }
}
