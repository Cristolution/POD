@extends('layouts.app', ['title' => __('designer_mapping_edit_title')])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            __('breadcrumb_home') => route('home'),
            __('breadcrumb_designer') => route('designer.dashboard'),
            __('breadcrumb_mappings') => route('designer.mappings'),
            ($mapping->design?->title ?? __('mapping')) => route('designer.mappings'),
            __('breadcrumb_edit') => route('designer.mappings.edit', $mapping),
        ]" />

        <h1 class="heading-1 mb-8">{{ __('designer_mapping_edit_heading') }}<span class="text-coral-500">.</span></h1>

        @if ($errors->any())
            <div class="card mb-6 border-coral-500">
                <p class="font-display uppercase text-coral-500 text-sm">{{ __('form_errors_heading') }}</p>
                <ul class="font-mono text-xs text-ink-700 mt-2 list-disc ps-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            @include('partials.designer-sidebar', ['active' => route('designer.mappings')])

            <div class="space-y-6">
                {{-- Immutable context for orientation --}}
                <div class="card">
                    <h2 class="heading-3 mb-4">{{ __('designer_mapping_context') }}</h2>
                    <dl class="grid sm:grid-cols-2 gap-y-3 gap-x-6 font-mono text-sm">
                        <dt class="text-ink-700">{{ __('designer_column_design') }}</dt>
                        <dd>
                            <a href="{{ route('designer.designs.show', $mapping->design) }}" class="text-coral-500 hover:underline">
                                {{ $mapping->design?->title ?? '—' }}
                            </a>
                        </dd>
                        <dt class="text-ink-700">{{ __('designer_column_product_template') }}</dt>
                        <dd>{{ $mapping->productTemplate?->type ?? '—' }}</dd>
                        <dt class="text-ink-700">{{ __('designer_column_created') }}</dt>
                        <dd>{{ $mapping->created_at?->format('Y-m-d') ?? '—' }}</dd>
                    </dl>
                </div>

                <form method="POST" action="{{ route('designer.mappings.update', $mapping) }}" class="card space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="label" for="preferred_printer_id">{{ __('designer_field_preferred_printer') }}</label>
                        <select id="preferred_printer_id" name="preferred_printer_id" required class="input">
                            <option value="">{{ __('designer_select_printer') }}</option>
                            @foreach ($printers as $printer)
                                <option value="{{ $printer->id }}"
                                        @selected(old('preferred_printer_id', $mapping->preferred_printer_id) === $printer->id)>
                                    {{ $printer->company_name }} — {{ $printer->user?->name ?? '—' }}
                                </option>
                            @endforeach
                        </select>
                        @error('preferred_printer_id') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="final_price">{{ __('designer_field_final_price') }}</label>
                        <input id="final_price" name="final_price" type="number" step="0.01" min="0" required
                               value="{{ old('final_price', number_format((float) $mapping->final_price, 2, '.', '')) }}"
                               class="input font-mono">
                        @error('final_price') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <button type="submit" class="btn-coral">{{ __('designer_save_changes') }}</button>
                        <a href="{{ route('designer.mappings') }}" class="btn btn-secondary">{{ __('cancel') }}</a>
                        <button type="submit" form="delete-mapping-form" class="btn btn-secondary ms-auto"
                                onclick="return confirm('{{ __('designer_confirm_delete_mapping') }}');">
                            {{ __('designer_delete_mapping') }}
                        </button>
                    </div>
                </form>

                <form id="delete-mapping-form" method="POST" action="{{ route('designer.mappings.destroy', $mapping) }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    </section>
@endsection
