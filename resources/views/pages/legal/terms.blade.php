@extends('layouts.marketing', ['title' => __('legal_terms_title')])

@section('content')
    <article class="max-w-3xl mx-auto px-6 py-16 prose">
        <h1 class="heading-1 mb-2">{{ __('legal_terms_heading') }}</h1>
        <p class="font-mono text-xs mb-8">{{ __('legal_effective_date') }}</p>

        <h2 class="heading-2 mt-8 mb-4">{{ __('legal_terms_section_1_heading') }}</h2>
        <p>{{ __('legal_terms_section_1_body') }}</p>

        <h2 class="heading-2 mt-8 mb-4">{{ __('legal_terms_section_2_heading') }}</h2>
        <p>{{ __('legal_terms_section_2_body') }}</p>

        <h2 class="heading-2 mt-8 mb-4">{{ __('legal_terms_section_3_heading') }}</h2>
        <p>{{ __('legal_terms_section_3_body') }}</p>

        <h2 class="heading-2 mt-8 mb-4">{{ __('legal_terms_section_4_heading') }}</h2>
        <p>{{ __('legal_terms_section_4_body') }}</p>

        <h2 class="heading-2 mt-8 mb-4">{{ __('legal_terms_section_5_heading') }}</h2>
        <p>{{ __('legal_terms_section_5_body') }}</p>

        <h2 class="heading-2 mt-8 mb-4">{{ __('legal_terms_section_6_heading') }}</h2>
        <p>{{ __('legal_terms_section_6_body') }}</p>

        <h2 class="heading-2 mt-8 mb-4">{{ __('legal_terms_section_7_heading') }}</h2>
        <p>{{ __('legal_terms_section_7_body') }}</p>

        <h2 class="heading-2 mt-8 mb-4">{{ __('legal_terms_section_8_heading') }}</h2>
        <p>{{ __('legal_terms_section_8_body') }} <a href="mailto:legal@pod.example.com">legal@pod.example.com</a>.</p>

        <p class="font-mono text-xs mt-12 text-ink-700">
            {{-- NOTE: this is v1.2 platform terms. Production launch requires review by counsel before public exposure. --}}
        </p>
    </article>
@endsection
