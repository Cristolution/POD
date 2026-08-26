@extends('layouts.app', ['title' => 'Create design'])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Designer' => route('designer.dashboard'),
            'Create design' => route('designer.designs.create'),
        ]" />

        <h1 class="heading-1 mb-8">Create design<span class="text-coral-500">.</span></h1>

        @unless ($profile->is_verified ?? false)
            <div class="card mb-6 border-coral-500 bg-sand-50">
                <p class="font-display uppercase text-coral-500 text-sm">Pending admin review</p>
                <p class="font-mono text-xs text-ink-700 mt-2">
                    Your account is pending verification. Designs you create here will still be saved to your portfolio.
                </p>
            </div>
        @endunless

        @if ($errors->any())
            <div class="card mb-6 border-coral-500">
                <p class="font-display uppercase text-coral-500 text-sm">Please fix the errors below</p>
                <ul class="font-mono text-xs text-ink-700 mt-2 list-disc pl-5 space-y-1">
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
                    <label class="label" for="title">Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}" required
                           class="input" autocomplete="off">
                    @error('title') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="category_id">Category</label>
                    <select id="category_id" name="category_id" required class="input">
                        <option value="">Select a category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="status">Status</label>
                    <select id="status" name="status" class="input">
                        @foreach (['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'draft') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('status') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="tag_ids">Tags</label>
                    <select id="tag_ids" name="tag_ids[]" multiple class="input min-h-[140px]">
                        @foreach ($tags as $tag)
                            <option value="{{ $tag->id }}"
                                    @selected(collect(old('tag_ids', []))->contains($tag->id))>
                                {{ $tag->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="font-mono text-xs text-ink-700 mt-1">Hold Ctrl/Cmd to pick multiple.</p>
                    @error('tag_ids') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="mockup">Mockup image</label>
                    <input id="mockup" name="mockup" type="file" required accept="image/jpeg,image/png,image/webp,image/svg+xml"
                           class="input file:mr-4 file:py-2 file:px-4 file:border-0 file:bg-sand-200 file:font-mono file:text-ink-800">
                    <p class="font-mono text-xs text-ink-700 mt-1">jpg, png, webp, or svg. Max 10MB. Shown on the public catalog.</p>
                    @error('mockup') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="print_file">Print-ready file</label>
                    <input id="print_file" name="print_file" type="file" required accept="image/jpeg,image/png,image/webp,image/svg+xml"
                           class="input file:mr-4 file:py-2 file:px-4 file:border-0 file:bg-sand-200 file:font-mono file:text-ink-800">
                    <p class="font-mono text-xs text-ink-700 mt-1">The high-resolution file printers will use. Same formats, max 10MB.</p>
                    @error('print_file') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-coral">Create design</button>
                    <a href="{{ route('designer.dashboard') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </section>
@endsection
