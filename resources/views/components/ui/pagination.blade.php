@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="flex justify-center gap-2 mt-12 font-mono text-sm uppercase tracking-wider">
        @if ($paginator->onFirstPage())
            <span class="btn btn-secondary opacity-50 cursor-not-allowed">← {{ __('pagination_prev') }}</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-secondary">← {{ __('pagination_prev') }}</a>
        @endif

        <span class="px-4 py-3">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="btn">{{ __('pagination_next') }} →</a>
        @else
            <span class="btn opacity-50 cursor-not-allowed">{{ __('pagination_next') }} →</span>
        @endif
    </nav>
@endif
