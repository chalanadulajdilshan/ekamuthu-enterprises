<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'POS') · {{ $settings['store_name'] }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    @stack('head')
</head>
<body>
@php($u = auth()->user())
<div class="shell">
    <aside class="sidebar">
        <div class="brand">
            @if (file_exists(public_path('images/logo.png')))
                <img src="{{ asset('images/logo.png') }}" alt="Logo" style="max-height:52px;max-width:200px">
            @else
                <div class="brand-mark"><i class="fa-solid fa-code"></i></div>
                <div class="brand-text"><b>SOURCE CODE</b><small>THE EYE OF TECHNOLOGY</small></div>
            @endif
        </div>
        <nav class="nav">
            <a href="{{ route('pos') }}" class="{{ request()->routeIs('pos') ? 'active' : '' }}"><i class="fa-solid fa-cart-shopping"></i><span>POS (Sales)</span></a>
            @if ($u->isAdmin())
                <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*', 'categories.*') ? 'active' : '' }}"><i class="fa-solid fa-boxes-stacked"></i><span>Products</span></a>
                <a href="{{ route('inventory.index') }}" class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}"><i class="fa-solid fa-warehouse"></i><span>Inventory</span></a>
                <a href="{{ route('purchases.index') }}" class="{{ request()->routeIs('purchases.*') ? 'active' : '' }}"><i class="fa-solid fa-truck-ramp-box"></i><span>Purchases</span></a>
            @endif
            <a href="{{ route('stock-in.index') }}" class="{{ request()->routeIs('stock-in.*') ? 'active' : '' }}"><i class="fa-solid fa-barcode"></i><span>Stock In (Scan)</span></a>
            <a href="{{ route('barcodes.index') }}" class="{{ request()->routeIs('barcodes.*') ? 'active' : '' }}"><i class="fa-solid fa-print"></i><span>Print Barcodes</span></a>
            <a href="{{ route('cash-book.index') }}" class="{{ request()->routeIs('cash-book.*') ? 'active' : '' }}"><i class="fa-solid fa-book"></i><span>Cash Book</span></a>
            <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}"><i class="fa-solid fa-users"></i><span>Customers</span></a>
            @if ($u->isAdmin())
                <a href="{{ route('suppliers.index') }}" class="{{ request()->routeIs('suppliers.*') ? 'active' : '' }}"><i class="fa-solid fa-people-carry-box"></i><span>Suppliers</span></a>
                <details {{ request()->routeIs('reports.*') ? 'open' : '' }}>
                    <summary><i class="fa-solid fa-chart-column"></i><span>Reports</span><i class="fa-solid fa-chevron-down chev"></i></summary>
                    <div class="sub">
                        <a href="{{ route('reports.sales') }}" class="{{ request()->routeIs('reports.sales') ? 'active' : '' }}"><span>Sales Summary</span></a>
                        <a href="{{ route('reports.bills') }}" class="{{ request()->routeIs('reports.bills', 'reports.bill') ? 'active' : '' }}"><span>Bills</span></a>
                        <a href="{{ route('reports.products') }}" class="{{ request()->routeIs('reports.products') ? 'active' : '' }}"><span>Product Sales</span></a>
                        <a href="{{ route('reports.out-of-stock') }}" class="{{ request()->routeIs('reports.out-of-stock') ? 'active' : '' }}"><span>Out of Stock</span></a>
                    </div>
                </details>
                <a href="{{ route('settings') }}" class="{{ request()->routeIs('settings*') ? 'active' : '' }}"><i class="fa-solid fa-gear"></i><span>Settings</span></a>
            @endif
        </nav>
    </aside>

    <div class="nav-backdrop" data-nav-close></div>

    <header class="topbar">
        <button type="button" class="nav-toggle" data-nav-toggle aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
        <h1><i class="fa-solid fa-store"></i> @yield('heading', 'Hardware Store POS')</h1>
        <div class="spacer"></div>

        @if ($u->isAdmin() && $branches->count() > 1)
            <form method="post" action="{{ route('branch.switch') }}" class="pill-select">
                @csrf
                <span class="dot"></span>
                <select name="branch_id" onchange="this.form.submit()" aria-label="Store">
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" @selected($b->id === $currentBranch->id)>Store: {{ $b->name }}</option>
                    @endforeach
                </select>
                <i class="fa-solid fa-chevron-down caret"></i>
            </form>
        @else
            <div class="pill-static"><span class="dot"></span> Store: {{ $currentBranch->name }}</div>
        @endif

        <div class="user-menu">
            <button type="button" data-dropdown><i class="fa-solid fa-circle-user"></i> <span class="uname">{{ $u->name }}</span> <i class="fa-solid fa-chevron-down" style="font-size:12px"></i></button>
            <div class="dropdown">
                <div class="meta">{{ $u->email }}<br>{{ ucfirst($u->role) }}</div>
                <form method="post" action="{{ route('logout') }}">@csrf<button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Log out</button></form>
            </div>
        </div>
    </header>

    <main class="main">
        @if (session('ok'))
            <div class="alert alert-ok"><i class="fa-solid fa-circle-check"></i> {{ session('ok') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-err"><i class="fa-solid fa-triangle-exclamation"></i> {{ $errors->first() }}</div>
        @endif
        @yield('content')
    </main>

    @hasSection('statusbar')
        <footer class="statusbar">@yield('statusbar')</footer>
    @endif
</div>
<div class="toast-wrap" id="toasts"></div>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
<script src="{{ asset('js/scanner.js') }}?v={{ filemtime(public_path('js/scanner.js')) }}" data-lib="{{ asset('vendor/html5-qrcode/html5-qrcode.min.js') }}"></script>
@stack('scripts')
</body>
</html>
