@extends('layouts.app', ['title' => __('designer_edit_design_title')])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            __('breadcrumb_home') => route('home'),
            __('breadcrumb_designer') => route('designer.dashboard'),
            __('designer_edit_design') => route('designer.designs.edit', $design),
        ]" />

        <h1 class="heading-1 mb-8">{{ __('designer_edit_design_heading') }}<span class="text-coral-500">.</span></h1>

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
            @include('partials.designer-sidebar', ['active' => route('designer.designs.edit', $design)])

            <div class="space-y-6">
                {{-- Existing media preview --}}
                <div class="card">
                    <h2 class="heading-3 mb-4">{{ __('designer_current_files') }}</h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        @php
                            $mockup = $design->mockups->first();
                            $printFile = $design->printFiles->first();
                        @endphp
                        <div>
                            <p class="label mb-2">{{ __('designer_mockup_label') }}</p>
                            @if ($mockup)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($mockup->file_path) }}"
                                     alt="{{ __('designer_mockup_label') }}" class="border-3 border-ink-800 w-full">
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

                <form method="POST" action="{{ route('designer.designs.update', $design) }}"
                      enctype="multipart/form-data" class="card space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="label" for="title">{{ __('designer_field_title') }}</label>
                        <input id="title" name="title" type="text" value="{{ old('title', $design->title) }}" required
                               class="input" autocomplete="off">
                        @error('title') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="category_id">{{ __('designer_field_category') }}</label>
                        <select id="category_id" name="category_id" required class="input">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $design->category_id) == $category->id)>
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
                                <option value="{{ $value }}" @selected(old('status', $design->status) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('status') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="tag_ids">{{ __('designer_field_tags') }}</label>
                        <select id="tag_ids" name="tag_ids[]" multiple class="input min-h-[140px]">
                            @php $selectedTagIds = old('tag_ids', $design->tags->pluck('id')->all()); @endphp
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}" @selected(in_array($tag->id, $selectedTagIds, true))>
                                    {{ $tag->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('tag_ids') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="mockup">{{ __('designer_replace_mockup_optional') }}</label>
                        <input id="mockup" name="mockup" type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml"
                               class="input file:me-4 file:py-2 file:px-4 file:border-0 file:bg-sand-200 file:font-mono file:text-ink-800">
                        <p class="font-mono text-xs text-ink-700 mt-1">{{ __('designer_replace_mockup_helper') }}</p>
                        @error('mockup') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="print_file">{{ __('designer_replace_print_file_optional') }}</label>
                        <input id="print_file" name="print_file" type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml"
                               class="input file:me-4 file:py-2 file:px-4 file:border-0 file:bg-sand-200 file:font-mono file:text-ink-800">
                        <p class="font-mono text-xs text-ink-700 mt-1">{{ __('designer_replace_print_file_helper') }}</p>
                        @error('print_file') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <button type="submit" class="btn-coral">{{ __('designer_save_changes') }}</button>
                        <a href="{{ route('designer.designs.show', $design) }}" class="btn btn-secondary">{{ __('cancel') }}</a>
                        <button type="submit" form="delete-design-form" class="btn btn-secondary ms-auto"
                                onclick="return confirm('{{ __('designer_confirm_delete_design') }}');">
                            {{ __('designer_delete_design') }}
                        </button>
                    </div>
                </form>

                <form id="delete-design-form" method="POST" action="{{ route('designer.designs.destroy', $design) }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    </section>
@endsection
