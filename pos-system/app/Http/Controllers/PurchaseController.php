<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $q = DB::table('purchases as p')
            ->join('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->where('p.branch_id', app('currentBranch')->id)
            ->select('p.id', 'p.ref_no', 'p.invoice_no', 'p.purchase_date', 'p.total', 'p.paid', 's.name as supplier');

        if ($request->filled('from')) {
            $q->where('p.purchase_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $q->where('p.purchase_date', '<=', $request->to);
        }
        if ($request->filled('supplier')) {
            $q->where('p.supplier_id', (int) $request->supplier);
        }
        if ($term = trim((string) $request->q)) {
            $q->where(fn ($w) => $w->where('p.ref_no', 'like', "%{$term}%")->orWhere('p.invoice_no', 'like', "{$term}%"));
        }

        return view('purchases.index', [
            'purchases' => $q->orderByDesc('p.id')->paginate(25)->withQueryString(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return view('purchases.create', [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'invoice_no' => 'nullable|string|max:60',
            'discount' => 'nullable|numeric|min:0',
            'paid' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.qty' => 'required|numeric|gt:0',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.sell_price' => 'nullable|numeric|min:0',
        ]);

        $purchase = PurchaseService::create($data, $request->user(), app('currentBranch'));

        return redirect()->route('purchases.show', $purchase)->with('ok', "Purchase {$purchase->ref_no} saved and stock updated.");
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier:id,name,phone', 'user:id,name', 'items.product:id,code,name,unit']);

        return view('purchases.show', compact('purchase'));
    }
}
