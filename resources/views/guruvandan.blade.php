@if ($paginator->hasPages())
    <nav class="gv-pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <p class="gv-pagination__summary">
            Showing <strong>{{ $paginator->firstItem() }}</strong> to <strong>{{ $paginator->lastItem() }}</strong>
            of <strong>{{ $paginator->total() }}</strong> teachers
        </p>

        <div class="gv-pagination__links">
            @if ($paginator->onFirstPage())
                <span class="gv-pagination__item is-disabled" aria-disabled="true">Previous</span>
            @else
                <a class="gv-pagination__item" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="gv-pagination__item is-gap" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="gv-pagination__item is-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="gv-pagination__item" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="gv-pagination__item" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="gv-pagination__item is-disabled" aria-disabled="true">Next</span>
            @endif
        </div>
    </nav>
@endif
