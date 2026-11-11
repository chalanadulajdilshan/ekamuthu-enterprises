<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * "Print Barcodes" for staff: pick a GRN (purchase) of the current branch and
 * print labels for the items received on it. Uses the same label sheet as the
 * admin GRN page (purchases.labels).
 */
class BarcodeController extends Controller
{
    public function index(Request $request)
    {
        $q = DB::table('purchases as p')
            ->join('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->where('p.branch_id', app('currentBranch')->id)
            ->select('p.id', 'p.ref_no', 'p.invoice_no', 'p.purchase_date', 's.name as supplier',
                DB::raw('(SELECT COUNT(*) FROM '.px('purchase_items').' i WHERE i.purchase_id = '.px('p').'.id) AS lines_'),
                DB::raw('(SELECT COALESCE(SUM(i.qty), 0) FROM '.px('purchase_items').' i WHERE i.purchase_id = '.px('p').'.id) AS units'));

        if ($request->filled('from')) {
            $q->where('p.purchase_date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $q->where('p.purchase_date', '<=', $request->input('to'));
        }
        if ($term = trim((string) $request->input('q'))) {
            $q->where(fn ($w) => $w->where('p.ref_no', 'like', "%{$term}%")
                ->orWhere('p.invoice_no', 'like', "{$term}%")
                ->orWhere('s.name', 'like', "%{$term}%"));
        }

        return view('barcodes.index', [
            'purchases' => $q->orderByDesc('p.id')->paginate(20)->withQueryString(),
        ]);
    }

    public function grn(Purchase $purchase)
    {
        abort_unless((int) $purchase->branch_id === (int) app('currentBranch')->id, 404);
        $purchase->load('items.product:id,code,barcode,name,unit,sell_price');

        $labels = $purchase->items->map(fn ($it) => [
            'value' => (string) ($it->product->barcode ?: $it->product->code),
            'name' => $it->product->name,
            'price' => money($it->product->sell_price),
        ])->values();

        return view('purchases.labels', [
            'purchase' => $purchase,
            'labels' => $labels,
            'back' => route('barcodes.index'),
            'backText' => 'Choose another GRN',
        ]);
    }
}
