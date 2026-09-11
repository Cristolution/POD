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
            __('breadcrumb_home') => route('home'),
            __('breadcrumb_designer') => route('designer.dashboard'),
            $design->title => route('designer.designs.show', $design),
        ]" />

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <div>
                <h1 class="heading-1">{{ $design->title }}<span class="text-coral-500">.</span></h1>
                <p class="font-mono text-xs uppercase tracking-wider text-ink-700 mt-2">
                    {{ $design->category?->name ?? __('design_uncategorized') }}
                    · <span class="badge {{ $design->status === 'published' ? 'badge-coral' : '' }}">{{ $design->status }}</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('designer.designs.edit', $design) }}" class="btn-coral">{{ __('designer_edit_design') }}</a>
                <a href="{{ route('designer.dashboard') }}" class="btn btn-secondary">{{ __('designer_back_dashboard') }}</a>
            </div>
        </div>

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            @include('partials.designer-sidebar', ['active' => route('designer.designs.show', $design)])

            <div class="space-y-8">
                {{-- Thumbnails --}}
                <div class="card">
                    <h2 class="heading-3 mb-4">{{ __('designer_files_heading') }}</h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <p class="label mb-2">{{ __('designer_mockup_label') }}</p>
                            @if ($mockup)
                                <img src="{{ $thumbUrl }}" alt="{{ __('designer_mockup_label') }}" class="border-3 border-ink-800 w-full">
                            @else
                                <p class="font-mono text-xs text-ink-700">{{ __('designer_no_mockup_uploaded') }}</p>
                            @endif
                        </div>
                        <div>
                            <p class="label mb-2">{{ __('designer_print_file_label') }}</p>
                            @if ($printFile)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($printFile->file_path) }}"
                                     alt="{{ __('designer_print_file_label') }}" class="border-3 border-ink-800 w-full">
                            @else
                                <p class="font-mono text-xs text-ink-700">{{ __('designer_no_print_file_uploaded') }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tags --}}
                <div class="card">
                    <h2 class="heading-3 mb-4">{{ __('designer_tags_heading') }}</h2>
                    @if ($design->tags->isEmpty())
                        <p class="font-mono text-xs text-ink-700">{{ __('designer_no_tags') }}</p>
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
                        <h2 class="heading-3">{{ __('designer_product_mappings_heading') }}</h2>
                        <a href="{{ route('designer.mappings.create', ['design_id' => $design->id]) }}" class="btn-coral">{{ __('designer_mappings_add') }}</a>
                    </div>
                    @if ($design->mappings->isEmpty())
                        <p class="font-mono text-xs text-ink-700">
                            {{ __('designer_no_mappings_for_design') }}
                        </p>
                    @else
                        <div class="table-wrap"><table class="table-pod">
                            <thead>
                                <tr>
                                    <th>{{ __('designer_column_product_template') }}</th>
                                    <th>{{ __('designer_column_preferred_printer') }}</th>
                                    <th class="text-right">{{ __('designer_column_final_price') }}</th>
                                    <th class="text-right">{{ __('designer_column_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($design->mappings as $mapping)
                                    <tr>
                                        <td class="font-mono text-sm">{{ $mapping->productTemplate?->type ?? '—' }}</td>
                                        <td class="font-mono text-sm">{{ $mapping->preferredPrinter?->user?->name ?? '—' }}</td>
                                        <td class="font-mono text-sm text-right">
                                            ${{ number_format((float) $mapping->final_price, 2) }}
                                        </td>
                                        <td class="font-mono text-sm text-right">
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
                    @endif
                </div>

                {{-- Recent order items --}}
                <div class="card">
                    <h2 class="heading-3 mb-4">{{ __('designer_recent_orders_heading') }}</h2>
                    @if ($recentOrderItems->isEmpty())
                        <p class="font-mono text-xs text-ink-700">{{ __('designer_no_orders_yet') }}</p>
                    @else
                        <div class="table-wrap"><table class="table-pod">
                            <thead>
                                <tr>
                                    <th>{{ __('account_order_number') }}</th>
                                    <th>{{ __('designer_orders_column_variant') }}</th>
                                    <th>{{ __('designer_orders_column_printer') }}</th>
                                    <th class="text-right">{{ __('designer_orders_column_qty') }}</th>
                                    <th class="text-right">{{ __('designer_orders_column_unit_price') }}</th>
                                    <th>{{ __('account_order_status') }}</th>
                                    <th>{{ __('designer_orders_column_placed') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentOrderItems as $item)
                                    <tr>
                                        <td class="font-mono text-sm">{{ Str::limit($item->order_id, 8, '') }}</td>
                                        <td class="font-mono text-sm">{{ $item->productVariant?->label() ?? '—' }}</td>
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
                        </table></div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
