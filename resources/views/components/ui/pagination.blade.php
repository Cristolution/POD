@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="flex justify-center gap-2 mt-12 font-mono text-sm uppercase tracking-wider">
        @if ($paginator->onFirstPage())
            <span class="btn btn-secondary opacity-50 cursor-not-allowed">← Prev</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-secondary">← Prev</a>
        @endif

        <span class="px-4 py-3">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="btn">Next →</a>
        @else
            <span class="btn opacity-50 cursor-not-allowed">Next →</span>
        @endif
    </nav>
@endif
