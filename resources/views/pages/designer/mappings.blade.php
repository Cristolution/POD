@extends('layouts.app', ['title' => __('designer_mappings_title')])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            __('breadcrumb_home') => route('home'),
            __('breadcrumb_designer') => route('designer.dashboard'),
            __('breadcrumb_mappings') => route('designer.mappings'),
        ]" />

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <h1 class="heading-1">{{ __('designer_mappings_heading') }}<span class="text-coral-500">.</span></h1>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('designer.mappings.create') }}" class="btn-coral">{{ __('designer_mappings_new') }}</a>
                <a href="{{ route('designer.dashboard') }}" class="btn btn-secondary">{{ __('designer_back_dashboard') }}</a>
            </div>
        </div>

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            @include('partials.designer-sidebar', ['active' => route('designer.mappings')])

            <div class="card">
                @if ($mappings->isEmpty())
                    <div class="space-y-4">
                        <p class="font-mono text-sm text-ink-700">
                            {{ __('designer_mappings_empty') }}
                        </p>
                        <a href="{{ route('designer.mappings.create') }}" class="btn-coral inline-block">{{ __('designer_mappings_create_first') }}</a>
                    </div>
                @else
                    <div class="table-wrap"><table class="table-pod">
                        <thead>
                            <tr>
                                <th>{{ __('designer_column_design') }}</th>
                                <th>{{ __('designer_column_product_template') }}</th>
                                <th>{{ __('designer_column_preferred_printer') }}</th>
                                <th class="text-end">{{ __('designer_column_final_price') }}</th>
                                <th>{{ __('designer_column_created') }}</th>
                                <th class="text-end">{{ __('designer_column_actions') }}</th>
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
                                    <td class="font-mono text-sm text-end">
                                        ${{ number_format((float) $mapping->final_price, 2) }}
                                    </td>
                                    <td class="font-mono text-sm">{{ $mapping->created_at?->format('Y-m-d') }}</td>
                                    <td class="font-mono text-sm text-end">
                                        <div class="inline-flex items-center gap-2 justify-end">
                                            <a href="{{ route('designer.mappings.edit', $mapping) }}" class="text-coral-500 hover:underline">
                                                {{ __('designer_action_edit') }}
                                            </a>
                                            <form method="POST" action="{{ route('designer.mappings.destroy', $mapping) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-coral-500 hover:underline"
                                                        onclick="return confirm('{{ __('designer_confirm_delete_mapping') }}');">
                                                    {{ __('designer_action_delete') }}
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
