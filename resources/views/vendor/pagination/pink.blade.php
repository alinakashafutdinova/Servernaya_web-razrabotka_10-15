@if ($paginator->hasPages())
<nav class="page-nav" role="navigation" aria-label="Страницы списка">
    <span class="page-caption">Страница {{ $paginator->currentPage() }} из {{ $paginator->lastPage() }}</span>
    <div class="page-links">
        @if ($paginator->onFirstPage())
            <span class="page-link is-disabled" aria-disabled="true">← Назад</span>
        @else
            <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">← Назад</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="page-ellipsis" aria-hidden="true">…</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="page-link is-current" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="page-link" href="{{ $url }}" aria-label="Страница {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Далее →</a>
        @else
            <span class="page-link is-disabled" aria-disabled="true">Далее →</span>
        @endif
    </div>
</nav>
@endif
