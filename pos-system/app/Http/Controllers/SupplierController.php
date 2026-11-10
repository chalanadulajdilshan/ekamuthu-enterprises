<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $q = Supplier::query()->select('id', 'name', 'contact_person', 'phone', 'email', 'address', 'balance', 'is_active');
        if ($term = trim((string) $request->q)) {
            $q->where(fn ($w) => $w->where('name', 'like', "{$term}%")->orWhere('phone', 'like', "{$term}%"));
        }
        if ($request->input('filter') === 'due') {
            $q->where('balance', '>', 0);
        }

        return view('suppliers.index', ['suppliers' => $q->orderBy('name')->paginate(25)->withQueryString()]);
    }

    public function store(Request $request)
    {
        Supplier::create($this->validated($request));

        return back()->with('ok', 'Supplier added.');
    }

    public function show(Supplier $supplier)
    {
        return view('suppliers.show', [
            'supplier' => $supplier,
            'purchases' => DB::table('purchases')->where('supplier_id', $supplier->id)->orderByDesc('id')
                ->select('id', 'ref_no', 'invoice_no', 'purchase_date', 'total', 'paid')->paginate(15),
            'payments' => $supplier->payments()->latest('id')->limit(20)->get(),
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validated($request));

        return back()->with('ok', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchases()->exists() || $supplier->balance != 0) {
            $supplier->update(['is_active' => false]);

            return back()->with('ok', 'Supplier has history, so it was deactivated.');
        }
        $supplier->delete();

        return back()->with('ok', 'Supplier deleted.');
    }

    public function payment(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|gt:0',
            'method' => 'required|in:cash,card,bank,cheque',
            'note' => 'nullable|string|max:200',
        ]);

        DB::transaction(function () use ($data, $supplier, $request) {
            $supplier->payments()->create($data + ['branch_id' => app('currentBranch')->id, 'user_id' => $request->user()->id]);
            DB::table('suppliers')->where('id', $supplier->id)->decrement('balance', $data['amount']);
        });

        return back()->with('ok', 'Payment recorded.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'contact_person' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
            'address' => 'nullable|string|max:255',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
