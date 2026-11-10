@if ($paginator->hasPages())
    <nav class="pager">
        @if ($paginator->onFirstPage())
            <span class="arrow dis"><i class="fa-solid fa-chevron-left"></i></span>
        @else
            <a class="arrow" href="{{ $paginator->previousPageUrl() }}"><i class="fa-solid fa-chevron-left"></i></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="dis">…</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="on">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="arrow" href="{{ $paginator->nextPageUrl() }}"><i class="fa-solid fa-chevron-right"></i></a>
        @else
            <span class="arrow dis"><i class="fa-solid fa-chevron-right"></i></span>
        @endif
        <span class="muted" style="font-weight:400;margin-left:8px">{{ number_format($paginator->total()) }} records</span>
    </nav>
@endif
