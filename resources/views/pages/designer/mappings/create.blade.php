@extends('layouts.app', ['title' => 'New mapping'])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Designer' => route('designer.dashboard'),
            'Mappings' => route('designer.mappings'),
            'New mapping' => route('designer.mappings.create'),
        ]" />

        <h1 class="heading-1 mb-8">New mapping<span class="text-coral-500">.</span></h1>

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
            @include('partials.designer-sidebar', ['active' => route('designer.mappings')])

            <form method="POST" action="{{ route('designer.mappings.store') }}" class="card space-y-6">
                @csrf

                <div>
                    <label class="label" for="design_id">Design</label>
                    <select id="design_id" name="design_id" required class="input">
                        <option value="">Select one of your designs</option>
                        @foreach ($designs as $design)
                            <option value="{{ $design->id }}"
                                    @selected(old('design_id', $preselectedDesignId) === $design->id)>
                                {{ $design->title }}
                            </option>
                        @endforeach
                    </select>
                    @error('design_id') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="product_template_id">Product template</label>
                    <select id="product_template_id" name="product_template_id" required class="input">
                        <option value="">Select a product template</option>
                        @foreach ($templates as $template)
                            <option value="{{ $template->id }}"
                                    data-base-cost="{{ $template->base_cost }}"
                                    @selected(old('product_template_id') === $template->id)>
                                {{ $template->name }} ({{ $template->type }})
                            </option>
                        @endforeach
                    </select>
                    <p class="font-mono text-xs text-ink-700 mt-1">Picking a template pre-fills the final price with its base cost.</p>
                    @error('product_template_id') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="preferred_printer_id">Preferred printer</label>
                    <select id="preferred_printer_id" name="preferred_printer_id" required class="input">
                        <option value="">Select a printer</option>
                        @foreach ($printers as $printer)
                            <option value="{{ $printer->id }}"
                                    @selected(old('preferred_printer_id') === $printer->id)>
                                {{ $printer->company_name }} — {{ $printer->user?->name ?? '—' }}
                            </option>
                        @endforeach
                    </select>
                    @error('preferred_printer_id') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="final_price">Final price (USD)</label>
                    <input id="final_price" name="final_price" type="number" step="0.01" min="0" required
                           value="{{ old('final_price') }}"
                           class="input font-mono"
                           placeholder="0.00">
                    @error('final_price') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-coral">Create mapping</button>
                    <a href="{{ route('designer.mappings') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </section>

    <script>
        (function () {
            const templateSelect = document.getElementById('product_template_id');
            const priceInput = document.getElementById('final_price');
            if (! templateSelect || ! priceInput) return;

            const syncPrice = () => {
                const option = templateSelect.options[templateSelect.selectedIndex];
                if (! option) return;
                const baseCost = option.getAttribute('data-base-cost');
                if (baseCost && (priceInput.value === '' || priceInput.value === null)) {
                    priceInput.value = parseFloat(baseCost).toFixed(2);
                }
            };
            templateSelect.addEventListener('change', syncPrice);
            syncPrice();
        })();
    </script>
@endsection
