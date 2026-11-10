<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = Customer::query()->select('id', 'name', 'phone', 'email', 'address', 'balance', 'credit_limit', 'is_active');
        if ($term = trim((string) $request->q)) {
            // name/phone prefix search hits the indexes
            $q->where(fn ($w) => $w->where('name', 'like', "{$term}%")->orWhere('phone', 'like', "{$term}%"));
        }
        if ($request->input('filter') === 'due') {
            $q->where('balance', '>', 0);
        }

        return view('customers.index', ['customers' => $q->orderBy('name')->paginate(25)->withQueryString()]);
    }

    /** Async search for the POS customer picker. */
    public function search(Request $request)
    {
        $term = trim((string) $request->q);
        $q = Customer::query()->where('is_active', true)->select('id', 'name', 'phone', 'balance');
        if ($term !== '') {
            $q->where(fn ($w) => $w->where('name', 'like', "{$term}%")->orWhere('phone', 'like', "{$term}%"));
        }

        return response()->json($q->orderBy('name')->limit(20)->get());
    }

    public function store(Request $request)
    {
        $c = Customer::create($this->validated($request));

        if ($request->expectsJson()) {
            return response()->json(['id' => $c->id, 'name' => $c->name, 'phone' => $c->phone, 'balance' => 0]);
        }

        return back()->with('ok', 'Customer added.');
    }

    public function show(Customer $customer)
    {
        return view('customers.show', [
            'customer' => $customer,
            'sales' => DB::table('sales')->where('customer_id', $customer->id)->orderByDesc('id')
                ->select('id', 'bill_no', 'created_at', 'total', 'paid_amount', 'payment_method', 'status')->paginate(15),
            'payments' => $customer->payments()->latest('id')->limit(20)->get(),
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->validated($request));

        return back()->with('ok', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->sales()->exists() || $customer->balance != 0) {
            $customer->update(['is_active' => false]);

            return back()->with('ok', 'Customer has history, so it was deactivated.');
        }
        $customer->delete();

        return back()->with('ok', 'Customer deleted.');
    }

    public function payment(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|gt:0',
            'method' => 'required|in:cash,card,bank',
            'note' => 'nullable|string|max:200',
        ]);

        DB::transaction(function () use ($data, $customer, $request) {
            $customer->payments()->create($data + ['branch_id' => app('currentBranch')->id, 'user_id' => $request->user()->id]);
            DB::table('customers')->where('id', $customer->id)->decrement('balance', $data['amount']);
        });

        return back()->with('ok', 'Payment recorded.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
            'address' => 'nullable|string|max:255',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);
        $data['credit_limit'] = $data['credit_limit'] ?? 0;
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
