@extends('layouts.app', ['title' => 'My mappings'])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Designer' => route('designer.dashboard'),
            'Mappings' => route('designer.mappings'),
        ]" />

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <h1 class="heading-1">Product mappings<span class="text-coral-500">.</span></h1>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('designer.mappings.create') }}" class="btn-coral">+ New mapping</a>
                <a href="{{ route('designer.dashboard') }}" class="btn btn-secondary">Back to dashboard</a>
            </div>
        </div>

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            @include('partials.designer-sidebar', ['active' => route('designer.mappings')])

            <div class="card">
                @if ($mappings->isEmpty())
                    <div class="space-y-4">
                        <p class="font-mono text-sm text-ink-700">
                            No product templates map to your designs yet.
                        </p>
                        <a href="{{ route('designer.mappings.create') }}" class="btn-coral inline-block">+ Create your first mapping</a>
                    </div>
                @else
                    <div class="table-wrap"><table class="table-pod">
                        <thead>
                            <tr>
                                <th>Design</th>
                                <th>Product template</th>
                                <th>Preferred printer</th>
                                <th class="text-right">Final price</th>
                                <th>Created</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mappings as $mapping)
                                <tr>
                                    <td class="font-mono text-sm">
                                        <a href="{{ route('designer.designs.show', $mapping->design) }}" class="text-coral-500 hover:underline">
                                            {{ $mapping->design?->title ?? '—' }}
                                        </a>
                                    </td>
                                    <td class="font-mono text-sm">{{ $mapping->productTemplate?->type ?? '—' }}</td>
                                    <td class="font-mono text-sm">{{ $mapping->preferredPrinter?->user?->name ?? '—' }}</td>
                                    <td class="font-mono text-sm text-right">
                                        ${{ number_format((float) $mapping->final_price, 2) }}
                                    </td>
                                    <td class="font-mono text-sm">{{ $mapping->created_at?->format('Y-m-d') }}</td>
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
                    </table></div>

                    <div class="mt-6">
                        <x-ui.pagination :paginator="$mappings" />
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
