@extends('layouts.app')
@section('title', 'Settings')

@section('content')
<div class="page-head"><h2>Settings</h2></div>

<div class="grid-2" style="gap:16px;align-items:start">
    <form class="card card-pad" method="post" action="{{ route('settings.update') }}">
        @csrf @method('PUT')
        <h3 style="margin-top:0">Store & receipt</h3>
        <div class="field"><label class="lbl">Store name *</label><input class="input" name="store_name" value="{{ $settings['store_name'] }}" required maxlength="100"></div>
        <div class="field"><label class="lbl">Address</label><input class="input" name="store_address" value="{{ $settings['store_address'] }}" maxlength="255"></div>
        <div class="grid-2">
            <div class="field"><label class="lbl">Phone</label><input class="input" name="store_phone" value="{{ $settings['store_phone'] }}" maxlength="60"></div>
            <div class="field"><label class="lbl">Currency *</label><input class="input" name="currency" value="{{ $settings['currency'] }}" required maxlength="8"></div>
        </div>
        <div class="grid-3">
            <div class="field"><label class="lbl">Tax label *</label><input class="input" name="tax_label" value="{{ $settings['tax_label'] }}" required maxlength="20"></div>
            <div class="field"><label class="lbl">Tax rate % *</label><input class="input" type="number" step="0.01" min="0" max="100" name="tax_rate" value="{{ $settings['tax_rate'] }}" required></div>
            <div class="field"><label class="lbl">POS items / page *</label><input class="input" type="number" min="5" max="60" name="products_per_page" value="{{ $settings['products_per_page'] }}" required></div>
        </div>
        <div class="field"><label class="lbl">Receipt footer</label><input class="input" name="receipt_footer" value="{{ $settings['receipt_footer'] }}" maxlength="255"></div>
        <div class="field"><label class="check"><input type="checkbox" name="allow_negative_stock" value="1" @checked($settings['allow_negative_stock'])> Allow selling when stock is zero (negative stock)</label></div>
        <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Settings</button>
    </form>

    <div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-pad" style="display:flex;align-items:center;padding-bottom:6px"><h3 style="margin:0;flex:1">Branches</h3><button class="btn btn-sm btn-primary" data-open="mBranchNew"><i class="fa-solid fa-plus"></i> Add</button></div>
            <table class="table">
                <thead><tr><th>Code</th><th>Name</th><th>Phone</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach ($allBranches as $b)
                    <tr>
                        <td><b>{{ $b->code }}</b></td><td>{{ $b->name }}</td><td>{{ $b->phone }}</td>
                        <td>{!! $b->is_active ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-gray">Inactive</span>' !!}</td>
                        <td style="text-align:right"><button class="btn btn-sm" data-edit="mBranchEdit" data-action="{{ route('settings.branches.update', $b) }}" data-json="{{ json_encode($b->only(['name', 'code', 'address', 'phone', 'is_active'])) }}"><i class="fa-solid fa-pen"></i></button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="card-pad" style="display:flex;align-items:center;padding-bottom:6px"><h3 style="margin:0;flex:1">Users</h3><button class="btn btn-sm btn-primary" data-open="mUserNew"><i class="fa-solid fa-plus"></i> Add</button></div>
            <table class="table">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Branch</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach ($users as $usr)
                    <tr>
                        <td><b>{{ $usr->name }}</b></td><td>{{ $usr->email }}</td>
                        <td><span class="badge {{ $usr->role === 'admin' ? 'badge-blue' : 'badge-gray' }}">{{ ucfirst($usr->role) }}</span></td>
                        <td>{{ $usr->branch->name ?? 'All' }}</td>
                        <td>{!! $usr->is_active ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-gray">Disabled</span>' !!}</td>
                        <td style="text-align:right"><button class="btn btn-sm" data-edit="mUserEdit" data-action="{{ route('settings.users.update', $usr) }}" data-json="{{ json_encode($usr->only(['name', 'email', 'role', 'branch_id', 'is_active']) + ['password' => '']) }}"><i class="fa-solid fa-pen"></i></button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach (['mBranchNew' => ['Add Branch', route('settings.branches.store'), 'POST'], 'mBranchEdit' => ['Edit Branch', '', 'PUT']] as $id => [$title, $action, $verb])
<div class="modal-bg" id="{{ $id }}">
    <div class="modal">
        <form method="post" action="{{ $action }}">
            @csrf @if ($verb === 'PUT') @method('PUT') @endif
            <div class="modal-head"><h3>{{ $title }}</h3><button type="button" class="x" data-close>&times;</button></div>
            <div class="modal-body">
                <div class="grid-2">
                    <div class="field"><label class="lbl">Name *</label><input class="input" name="name" required maxlength="100"></div>
                    <div class="field"><label class="lbl">Code (bill prefix) *</label><input class="input" name="code" required maxlength="10" pattern="[A-Za-z0-9]+"></div>
                </div>
                <div class="field"><label class="lbl">Address</label><input class="input" name="address" maxlength="255"></div>
                <div class="field"><label class="lbl">Phone</label><input class="input" name="phone" maxlength="30"></div>
                <input type="hidden" name="is_active" value="0">
                <label class="check"><input type="checkbox" name="is_active" value="1" checked> Active</label>
            </div>
            <div class="modal-foot"><button type="button" class="btn" data-close>Cancel</button><button class="btn btn-primary">Save</button></div>
        </form>
    </div>
</div>
@endforeach

@foreach (['mUserNew' => ['Add User', route('settings.users.store'), 'POST'], 'mUserEdit' => ['Edit User', '', 'PUT']] as $id => [$title, $action, $verb])
<div class="modal-bg" id="{{ $id }}">
    <div class="modal">
        <form method="post" action="{{ $action }}">
            @csrf @if ($verb === 'PUT') @method('PUT') @endif
            <div class="modal-head"><h3>{{ $title }}</h3><button type="button" class="x" data-close>&times;</button></div>
            <div class="modal-body">
                <div class="grid-2">
                    <div class="field"><label class="lbl">Name *</label><input class="input" name="name" required maxlength="100"></div>
                    <div class="field"><label class="lbl">Email *</label><input class="input" type="email" name="email" required></div>
                </div>
                <div class="grid-2">
                    <div class="field"><label class="lbl">Role</label><select class="input" name="role"><option value="cashier">Cashier</option><option value="admin">Admin</option></select></div>
                    <div class="field"><label class="lbl">Branch</label>
                        <select class="input" name="branch_id"><option value="">All branches</option>@foreach ($allBranches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
                </div>
                <div class="field"><label class="lbl">Password {{ $verb === 'PUT' ? '(leave blank to keep)' : '*' }}</label><input class="input" type="password" name="password" minlength="6" {{ $verb === 'POST' ? 'required' : '' }} autocomplete="new-password"></div>
                <input type="hidden" name="is_active" value="0">
                <label class="check"><input type="checkbox" name="is_active" value="1" checked> Active</label>
            </div>
            <div class="modal-foot"><button type="button" class="btn" data-close>Cancel</button><button class="btn btn-primary">Save</button></div>
        </form>
    </div>
</div>
@endforeach
@endsection
