@extends('layouts.app', ['title' => $design->title])

@section('content')
    @php
        $mockup = $design->mockups->first();
        $printFile = $design->printFiles->first();
        $thumbUrl = $mockup
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($mockup->file_path)
            : null;
    @endphp

    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Designer' => route('designer.dashboard'),
            $design->title => route('designer.designs.show', $design),
        ]" />

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <div>
                <h1 class="heading-1">{{ $design->title }}<span class="text-coral-500">.</span></h1>
                <p class="font-mono text-xs uppercase tracking-wider text-ink-700 mt-2">
                    {{ $design->category?->name ?? 'Uncategorized' }}
                    · <span class="badge {{ $design->status === 'published' ? 'badge-coral' : '' }}">{{ $design->status }}</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('designer.designs.edit', $design) }}" class="btn-coral">Edit design</a>
                <a href="{{ route('designer.dashboard') }}" class="btn btn-secondary">Back to dashboard</a>
            </div>
        </div>

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            @include('partials.designer-sidebar', ['active' => route('designer.designs.show', $design)])

            <div class="space-y-8">
                {{-- Thumbnails --}}
                <div class="card">
                    <h2 class="heading-3 mb-4">Files</h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <p class="label mb-2">Mockup</p>
                            @if ($mockup)
                                <img src="{{ $thumbUrl }}" alt="Mockup" class="border-3 border-ink-800 w-full">
                            @else
                                <p class="font-mono text-xs text-ink-700">No mockup uploaded.</p>
                            @endif
                        </div>
                        <div>
                            <p class="label mb-2">Print file</p>
                            @if ($printFile)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($printFile->file_path) }}"
                                     alt="Print file" class="border-3 border-ink-800 w-full">
                            @else
                                <p class="font-mono text-xs text-ink-700">No print file uploaded.</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tags --}}
                <div class="card">
                    <h2 class="heading-3 mb-4">Tags</h2>
                    @if ($design->tags->isEmpty())
                        <p class="font-mono text-xs text-ink-700">No tags.</p>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($design->tags as $tag)
                                <span class="badge">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Mappings --}}
                <div class="card">
                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-4">
                        <h2 class="heading-3">Product mappings</h2>
                        <a href="{{ route('designer.mappings.create', ['design_id' => $design->id]) }}" class="btn-coral">+ Add mapping</a>
                    </div>
                    @if ($design->mappings->isEmpty())
                        <p class="font-mono text-xs text-ink-700">
                            No product templates map to this design yet.
                        </p>
                    @else
                        <table class="table-pod">
                            <thead>
                                <tr>
                                    <th>Product template</th>
                                    <th>Preferred printer</th>
                                    <th class="text-right">Final price</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($design->mappings as $mapping)
                                    <tr>
                                        <td class="font-mono text-sm">{{ $mapping->productTemplate?->name ?? '—' }}</td>
                                        <td class="font-mono text-sm">{{ $mapping->preferredPrinter?->user?->name ?? '—' }}</td>
                                        <td class="font-mono text-sm text-right">
                                            ${{ number_format((float) $mapping->final_price, 2) }}
                                        </td>
                                        <td class="font-mono text-sm text-right">
                                            <div class="inline-flex items-center gap-2 justify-end">
                                                <a href="{{ route('designer.mappings.edit', $mapping) }}" class="text-coral-500 hover:underline">
                                                    Edit
                                                </a>
                                                <form method="POST" action="{{ route('designer.mappings.destroy', $mapping) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-coral-500 hover:underline"
                                                            onclick="return confirm('Delete this mapping? This cannot be undone if it has no order items.');">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                {{-- Recent order items --}}
                <div class="card">
                    <h2 class="heading-3 mb-4">Recent orders</h2>
                    @if ($recentOrderItems->isEmpty())
                        <p class="font-mono text-xs text-ink-700">No orders yet for this design.</p>
                    @else
                        <table class="table-pod">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Variant</th>
                                    <th>Printer</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Unit price</th>
                                    <th>Status</th>
                                    <th>Placed</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentOrderItems as $item)
                                    <tr>
                                        <td class="font-mono text-sm">{{ Str::limit($item->order_id, 8, '') }}</td>
                                        <td class="font-mono text-sm">{{ $item->productVariant?->name ?? '—' }}</td>
                                        <td class="font-mono text-sm">{{ $item->printerProvider?->user?->name ?? '—' }}</td>
                                        <td class="font-mono text-sm text-right">{{ $item->quantity }}</td>
                                        <td class="font-mono text-sm text-right">
                                            ${{ number_format((float) $item->unit_price, 2) }}
                                        </td>
                                        <td><span class="badge">{{ $item->status }}</span></td>
                                        <td class="font-mono text-sm">{{ $item->created_at?->format('Y-m-d') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
