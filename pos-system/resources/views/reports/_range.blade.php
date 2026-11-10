<form class="filters" method="get">
    <div><label class="lbl">From</label><input class="input" type="date" name="from" value="{{ $range[0] }}"></div>
    <div><label class="lbl">To</label><input class="input" type="date" name="to" value="{{ $range[1] }}"></div>
    {{ $slot ?? '' }}
    <button class="btn btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
    <a class="btn btn-sm btn-ghost" href="?from={{ now()->toDateString() }}&to={{ now()->toDateString() }}">Today</a>
    <a class="btn btn-sm btn-ghost" href="?from={{ now()->subDays(6)->toDateString() }}&to={{ now()->toDateString() }}">7 days</a>
    <a class="btn btn-sm btn-ghost" href="?from={{ now()->startOfMonth()->toDateString() }}&to={{ now()->toDateString() }}">This month</a>
</form>
