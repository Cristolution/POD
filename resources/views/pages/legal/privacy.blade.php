@extends('layouts.marketing', ['title' => 'Privacy policy'])

@section('content')
    <article class="max-w-3xl mx-auto px-6 py-16 prose">
        <h1 class="heading-1 mb-2">Privacy policy</h1>
        <p class="font-mono text-xs mb-8">Effective: 2026-08-23</p>

        <h2 class="heading-2 mt-8 mb-4">1. Data we collect</h2>
        <ul>
            <li><strong>Account data:</strong> name, email, phone (optional), role.</li>
            <li><strong>Order data:</strong> shipping + billing address, payment method (provider token only — we do not store card numbers), order history.</li>
            <li><strong>Designs:</strong> any media you upload as a designer.</li>
            <li><strong>Usage data:</strong> requests are logged for 30 days for security and debugging.</li>
        </ul>

        <h2 class="heading-2 mt-8 mb-4">2. How we use it</h2>
        <p>To operate the Service: fulfill orders, process payments, send transactional notifications (order placed / shipped / delivered), prevent fraud, and improve the platform.</p>

        <h2 class="heading-2 mt-8 mb-4">3. Sharing</h2>
        <p>We share data only with the parties needed to fulfill your order (the printer assigned to each order item; the delivery company; the payment provider). We do not sell personal data.</p>

        <h2 class="heading-2 mt-8 mb-4">4. Cookies and tokens</h2>
        <p>We use a session cookie for web authentication and an optional Sanctum bearer token (stored in an HttpOnly cookie) for API access. No third-party advertising cookies.</p>

        <h2 class="heading-2 mt-8 mb-4">5. Retention</h2>
        <p>Account data is retained while the account is active. Soft-deleted records are purged after 90 days. Order records are retained for 7 years for tax compliance.</p>

        <h2 class="heading-2 mt-8 mb-4">6. Your rights</h2>
        <p>You can export your account data (settings → account export) and request deletion at any time, subject to retention requirements.</p>

        <h2 class="heading-2 mt-8 mb-4">7. Contact</h2>
        <p>Privacy questions: <a href="mailto:privacy@pod.example.com">privacy@pod.example.com</a>.</p>

        <p class="font-mono text-xs mt-12 text-ink-700">
            {{-- NOTE: this is v1.2 platform privacy policy. Production launch requires review by counsel before public exposure. --}}
        </p>
    </article>
@endsection
