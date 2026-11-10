@extends('layouts.app')
@section('title', 'Suppliers')

@section('content')
<div class="page-head">
    <h2>Suppliers</h2>
    <div class="spacer"></div>
    <button class="btn btn-primary" data-open="mNew"><i class="fa-solid fa-plus"></i> New Supplier</button>
</div>

<div class="card card-pad">
    <form class="filters" method="get">
        <input class="input" name="q" value="{{ request('q') }}" placeholder="Name or phone starts with..." style="min-width:260px">
        <select class="input" name="filter">
            <option value="">All suppliers</option>
            <option value="due" @selected(request('filter') === 'due')>We owe</option>
        </select>
        <button class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Name</th><th>Contact</th><th>Phone</th><th>Email</th><th class="num">Payable</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($suppliers as $s)
                <tr>
                    <td><a href="{{ route('suppliers.show', $s) }}"><b>{{ $s->name }}</b></a></td>
                    <td>{{ $s->contact_person }}</td>
                    <td>{{ $s->phone }}</td>
                    <td>{{ $s->email }}</td>
                    <td class="num">@if ($s->balance > 0)<span class="badge badge-amber">{{ money($s->balance) }}</span>@else 0.00 @endif</td>
                    <td>{!! $s->is_active ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-gray">Inactive</span>' !!}</td>
                    <td style="text-align:right;white-space:nowrap">
                        <a class="btn btn-sm" href="{{ route('suppliers.show', $s) }}"><i class="fa-regular fa-eye"></i></a>
                        <button class="btn btn-sm" data-edit="mEdit" data-action="{{ route('suppliers.update', $s) }}"
                                data-json="{{ json_encode($s->only(['name', 'contact_person', 'phone', 'email', 'address', 'is_active'])) }}"><i class="fa-solid fa-pen"></i></button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">No suppliers found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $suppliers->links() }}
</div>

@include('suppliers._form', ['id' => 'mNew', 'title' => 'New Supplier', 'action' => route('suppliers.store'), 'verb' => 'POST'])
@include('suppliers._form', ['id' => 'mEdit', 'title' => 'Edit Supplier', 'action' => '', 'verb' => 'PUT'])
@endsection
