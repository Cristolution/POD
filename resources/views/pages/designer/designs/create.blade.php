@extends('layouts.app', ['title' => __('designer_create_design_title')])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            __('breadcrumb_home') => route('home'),
            __('breadcrumb_designer') => route('designer.dashboard'),
            __('designer_create_design') => route('designer.designs.create'),
        ]" />

        <h1 class="heading-1 mb-8">{{ __('designer_create_design_heading') }}<span class="text-coral-500">.</span></h1>

        @unless ($profile->is_verified ?? false)
            <div class="card mb-6 border-coral-500 bg-sand-50">
                <p class="font-display uppercase text-coral-500 text-sm">{{ __('pending_review_heading') }}</p>
                <p class="font-mono text-xs text-ink-700 mt-2">
                    {{ __('pending_review_design_body') }}
                </p>
            </div>
        @endunless

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
            @include('partials.designer-sidebar', ['active' => route('designer.designs.create')])

            <form method="POST" action="{{ route('designer.designs.store') }}"
                  enctype="multipart/form-data" class="card space-y-6">
                @csrf

                <div>
                    <label class="label" for="title">{{ __('designer_field_title') }}</label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}" required
                           class="input" autocomplete="off">
                    @error('title') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="category_id">{{ __('designer_field_category') }}</label>
                    <select id="category_id" name="category_id" required class="input">
                        <option value="">{{ __('designer_select_category') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="status">{{ __('designer_field_status') }}</label>
                    <select id="status" name="status" class="input">
                        @foreach (['draft' => __('design_status_draft'), 'published' => __('design_status_published'), 'archived' => __('design_status_archived')] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'draft') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('status') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="tag_ids">{{ __('designer_field_tags') }}</label>
                    <select id="tag_ids" name="tag_ids[]" multiple class="input min-h-[140px]">
                        @foreach ($tags as $tag)
                            <option value="{{ $tag->id }}"
                                    @selected(collect(old('tag_ids', []))->contains($tag->id))>
                                {{ $tag->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="font-mono text-xs text-ink-700 mt-1">{{ __('designer_field_tags_helper') }}</p>
                    @error('tag_ids') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="mockup">{{ __('designer_field_mockup') }}</label>
                    <input id="mockup" name="mockup" type="file" required accept="image/jpeg,image/png,image/webp,image/svg+xml"
                           class="input file:me-4 file:py-2 file:px-4 file:border-0 file:bg-sand-200 file:font-mono file:text-ink-800">
                    <p class="font-mono text-xs text-ink-700 mt-1">{{ __('designer_field_mockup_helper') }}</p>
                    @error('mockup') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="print_file">{{ __('designer_field_print_file') }}</label>
                    <input id="print_file" name="print_file" type="file" required accept="image/jpeg,image/png,image/webp,image/svg+xml"
                           class="input file:me-4 file:py-2 file:px-4 file:border-0 file:bg-sand-200 file:font-mono file:text-ink-800">
                    <p class="font-mono text-xs text-ink-700 mt-1">{{ __('designer_field_print_file_helper') }}</p>
                    @error('print_file') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- OPTIONAL: per-product mockups --}}
                {{-- Designer can drop a custom preview for specific product types. The
                     storefront falls back to the default mockup when no override exists,
                     so designers only upload the products they actually want a custom
                     preview for. --}}
                <div class="border-3 border-dashed border-ink-800 bg-sand-50 p-4 space-y-3">
                    <div class="flex items-baseline justify-between gap-4">
                        <p class="font-display uppercase text-sm">{{ __('designer_per_product_mockups') }} <span class="font-mono normal-case text-xs text-ink-700">{{ __('designer_optional_suffix') }}</span></p>
                        <p class="font-mono text-xs text-ink-700">{{ __('designer_per_product_mockups_helper') }}</p>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-3">
                        @foreach (($productTypes ?? []) as $type)
                            <div>
                                <label class="label" for="product_mockup_{{ $type }}">{{ ucwords(str_replace('-', ' ', $type)) }} {{ __('designer_mockup_label') }}</label>
                                <input id="product_mockup_{{ $type }}"
                                       name="product_mockups[{{ $type }}]"
                                       type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml"
                                       class="input file:me-4 file:py-2 file:px-4 file:border-0 file:bg-sand-200 file:font-mono file:text-ink-800">
                                @error("product_mockups.$type") <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-coral">{{ __('designer_create_design') }}</button>
                    <a href="{{ route('designer.dashboard') }}" class="btn btn-secondary">{{ __('cancel') }}</a>
                </div>
            </form>
        </div>
    </section>
@endsection
