@extends('layouts.app')
@section('title', 'Customers')

@section('content')
<div class="page-head">
    <h2>Customers</h2>
    <div class="spacer"></div>
    <button class="btn btn-primary" data-open="mNew"><i class="fa-solid fa-user-plus"></i> New Customer</button>
</div>

<div class="card card-pad">
    <form class="filters" method="get">
        <input class="input" name="q" value="{{ request('q') }}" placeholder="Name or phone starts with..." style="min-width:260px">
        <select class="input" name="filter">
            <option value="">All customers</option>
            <option value="due" @selected(request('filter') === 'due')>With outstanding</option>
        </select>
        <button class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th class="num">Credit limit</th><th class="num">Outstanding</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($customers as $c)
                <tr>
                    <td><a href="{{ route('customers.show', $c) }}"><b>{{ $c->name }}</b></a></td>
                    <td>{{ $c->phone }}</td>
                    <td>{{ $c->email }}</td>
                    <td class="num">{{ money($c->credit_limit) }}</td>
                    <td class="num">@if ($c->balance > 0)<span class="badge badge-amber">{{ money($c->balance) }}</span>@else 0.00 @endif</td>
                    <td>{!! $c->is_active ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-gray">Inactive</span>' !!}</td>
                    <td style="text-align:right;white-space:nowrap">
                        <a class="btn btn-sm" href="{{ route('customers.show', $c) }}"><i class="fa-regular fa-eye"></i></a>
                        <button class="btn btn-sm" data-edit="mEdit" data-action="{{ route('customers.update', $c) }}"
                                data-json="{{ json_encode($c->only(['name', 'phone', 'email', 'address', 'credit_limit', 'is_active'])) }}"><i class="fa-solid fa-pen"></i></button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">No customers found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $customers->links() }}
</div>

@include('customers._form', ['id' => 'mNew', 'title' => 'New Customer', 'action' => route('customers.store'), 'verb' => 'POST'])
@include('customers._form', ['id' => 'mEdit', 'title' => 'Edit Customer', 'action' => '', 'verb' => 'PUT'])
@endsection
