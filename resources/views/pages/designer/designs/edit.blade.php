@extends('layouts.app', ['title' => 'Edit design'])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Designer' => route('designer.dashboard'),
            'Edit design' => route('designer.designs.edit', $design),
        ]" />

        <h1 class="heading-1 mb-8">Edit design<span class="text-coral-500">.</span></h1>

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
            @include('partials.designer-sidebar', ['active' => route('designer.designs.edit', $design)])

            <div class="space-y-6">
                {{-- Existing media preview --}}
                <div class="card">
                    <h2 class="heading-3 mb-4">Current files</h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        @php
                            $mockup = $design->mockups->first();
                            $printFile = $design->printFiles->first();
                        @endphp
                        <div>
                            <p class="label mb-2">Mockup</p>
                            @if ($mockup)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($mockup->file_path) }}"
                                     alt="Mockup" class="border-3 border-ink-800 w-full">
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

                <form method="POST" action="{{ route('designer.designs.update', $design) }}"
                      enctype="multipart/form-data" class="card space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="label" for="title">Title</label>
                        <input id="title" name="title" type="text" value="{{ old('title', $design->title) }}" required
                               class="input" autocomplete="off">
                        @error('title') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="category_id">Category</label>
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
                        <label class="label" for="status">Status</label>
                        <select id="status" name="status" class="input">
                            @foreach (['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $design->status) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('status') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="tag_ids">Tags</label>
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
                        <label class="label" for="mockup">Replace mockup (optional)</label>
                        <input id="mockup" name="mockup" type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml"
                               class="input file:mr-4 file:py-2 file:px-4 file:border-0 file:bg-sand-200 file:font-mono file:text-ink-800">
                        <p class="font-mono text-xs text-ink-700 mt-1">Leave blank to keep the current mockup. Max 10MB.</p>
                        @error('mockup') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="print_file">Replace print file (optional)</label>
                        <input id="print_file" name="print_file" type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml"
                               class="input file:mr-4 file:py-2 file:px-4 file:border-0 file:bg-sand-200 file:font-mono file:text-ink-800">
                        <p class="font-mono text-xs text-ink-700 mt-1">Leave blank to keep the current print file. Max 10MB.</p>
                        @error('print_file') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <button type="submit" class="btn-coral">Save changes</button>
                        <a href="{{ route('designer.designs.show', $design) }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" form="delete-design-form" class="btn btn-secondary ml-auto"
                                onclick="return confirm('Delete this design? It will be soft-deleted and hidden from your dashboard.');">
                            Delete design
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
