@extends('layouts.marketing', ['title' => __('legal_privacy_title')])

@section('content')
    <article class="max-w-3xl mx-auto px-6 py-16 prose">
        <h1 class="heading-1 mb-2">{{ __('legal_privacy_heading') }}</h1>
        <p class="font-mono text-xs mb-8">{{ __('legal_effective_date') }}</p>

        <h2 class="heading-2 mt-8 mb-4">{{ __('legal_privacy_section_1_heading') }}</h2>
        <ul>
            <li><strong>{{ __('legal_privacy_section_1_account') }}</strong></li>
            <li><strong>{{ __('legal_privacy_section_1_orders') }}</strong></li>
            <li><strong>{{ __('legal_privacy_section_1_designs') }}</strong></li>
            <li><strong>{{ __('legal_privacy_section_1_usage') }}</strong></li>
        </ul>

        <h2 class="heading-2 mt-8 mb-4">{{ __('legal_privacy_section_2_heading') }}</h2>
        <p>{{ __('legal_privacy_section_2_body') }}</p>

        <h2 class="heading-2 mt-8 mb-4">{{ __('legal_privacy_section_3_heading') }}</h2>
        <p>{{ __('legal_privacy_section_3_body') }}</p>

        <h2 class="heading-2 mt-8 mb-4">{{ __('legal_privacy_section_4_heading') }}</h2>
        <p>{{ __('legal_privacy_section_4_body') }}</p>

        <h2 class="heading-2 mt-8 mb-4">{{ __('legal_privacy_section_5_heading') }}</h2>
        <p>{{ __('legal_privacy_section_5_body') }}</p>

        <h2 class="heading-2 mt-8 mb-4">{{ __('legal_privacy_section_6_heading') }}</h2>
        <p>{{ __('legal_privacy_section_6_body') }}</p>

        <h2 class="heading-2 mt-8 mb-4">{{ __('legal_privacy_section_7_heading') }}</h2>
        <p>{{ __('legal_privacy_section_7_body') }} <a href="mailto:privacy@pod.example.com">privacy@pod.example.com</a>.</p>

        <p class="font-mono text-xs mt-12 text-ink-700">
            {{-- NOTE: this is v1.2 platform privacy policy. Production launch requires review by counsel before public exposure. --}}
        </p>
    </article>
@endsection
