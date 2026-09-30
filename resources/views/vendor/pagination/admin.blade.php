<nav class="admin-pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
    <p class="admin-pagination__summary">
        Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} results
    </p>

    @if ($paginator->hasPages())
        <div class="admin-pagination__controls">
            @if ($paginator->onFirstPage())
                <span class="admin-pagination__link admin-pagination__previous is-disabled" aria-disabled="true">
                    <span aria-hidden="true">&lsaquo;</span> Previous
                </span>
            @else
                <a class="admin-pagination__link admin-pagination__previous" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                    <span aria-hidden="true">&lsaquo;</span> Previous
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="admin-pagination__ellipsis" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="admin-pagination__link admin-pagination__page is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="admin-pagination__link admin-pagination__page" href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="admin-pagination__link admin-pagination__next" href="{{ $paginator->nextPageUrl() }}" rel="next">
                    Next <span aria-hidden="true">&rsaquo;</span>
                </a>
            @else
                <span class="admin-pagination__link admin-pagination__next is-disabled" aria-disabled="true">
                    Next <span aria-hidden="true">&rsaquo;</span>
                </span>
            @endif
        </div>
    @endif
</nav>