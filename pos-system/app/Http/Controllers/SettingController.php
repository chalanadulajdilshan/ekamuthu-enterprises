<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'allBranches' => Branch::orderBy('id')->get(),
            'users' => User::with('branch:id,name')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'store_name' => 'required|string|max:100',
            'store_address' => 'nullable|string|max:255',
            'store_phone' => 'nullable|string|max:60',
            'currency' => 'required|string|max:8',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'tax_label' => 'required|string|max:20',
            'receipt_footer' => 'nullable|string|max:255',
            'products_per_page' => 'required|integer|min:5|max:60',
        ]);
        $data['allow_negative_stock'] = $request->boolean('allow_negative_stock') ? '1' : '0';
        Setting::store($data);

        return back()->with('ok', 'Settings saved.');
    }

    public function storeBranch(Request $request)
    {
        Branch::create($this->branchData($request));
        Cache::forget('branches.active');

        return back()->with('ok', 'Branch added.');
    }

    public function updateBranch(Request $request, Branch $branch)
    {
        $branch->update($this->branchData($request, $branch));
        Cache::forget('branches.active');

        return back()->with('ok', 'Branch updated.');
    }

    public function storeUser(Request $request)
    {
        User::create($this->userData($request));

        return back()->with('ok', 'User added.');
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $this->userData($request, $user);
        if ($user->id === $request->user()->id) {
            $data['role'] = 'admin';
            $data['is_active'] = true; // cannot lock yourself out
        }
        $user->update($data);

        return back()->with('ok', 'User updated.');
    }

    private function branchData(Request $request, ?Branch $branch = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'code' => ['required', 'alpha_num', 'max:10', Rule::unique('branches')->ignore($branch)],
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
        ]);
        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    private function userData(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user)],
            'role' => 'required|in:admin,cashier',
            'branch_id' => 'nullable|exists:branches,id',
            'password' => [$user ? 'nullable' : 'required', Password::min(6)],
        ]);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
