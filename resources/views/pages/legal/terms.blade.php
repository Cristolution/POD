@extends('layouts.marketing', ['title' => 'Terms of service'])

@section('content')
    <article class="max-w-3xl mx-auto px-6 py-16 prose">
        <h1 class="heading-1 mb-2">Terms of service</h1>
        <p class="font-mono text-xs mb-8">Effective: 2026-08-23</p>

        <h2 class="heading-2 mt-8 mb-4">1. Acceptance</h2>
        <p>By creating an account on POD Platform ("the Service") you agree to these terms. If you do not agree, do not use the Service.</p>

        <h2 class="heading-2 mt-8 mb-4">2. Accounts and roles</h2>
        <p>The Service supports four roles: <strong>customer</strong> (browses and orders), <strong>designer</strong> (publishes designs), <strong>printer_provider</strong> (fulfills orders), and <strong>admin</strong> (operates the platform). Each role has specific permissions defined in <code>PermissionMatrix-v1.2</code>.</p>

        <h2 class="heading-2 mt-8 mb-4">3. Orders and payments</h2>
        <p>Orders are placed via the checkout flow at <code>/checkout</code>. Prices are snapshotted on <code>order_items.unit_price</code> at placement time. The platform does not charge tax in v1.2.</p>

        <h2 class="heading-2 mt-8 mb-4">4. Designs and intellectual property</h2>
        <p>Designers retain copyright on designs they upload. By publishing a design you grant the platform a non-exclusive license to reproduce the design on the chosen product templates for the purpose of fulfilling orders.</p>

        <h2 class="heading-2 mt-8 mb-4">5. Fulfillment and shipping</h2>
        <p>Printers are independent contractors. Delivery times are estimates, not guarantees. Risk of loss passes to the customer upon carrier acceptance.</p>

        <h2 class="heading-2 mt-8 mb-4">6. Cancellations and refunds</h2>
        <p>Orders may be cancelled before the status transitions to <code>shipped</code>. After shipment, refunds are handled case-by-case.</p>

        <h2 class="heading-2 mt-8 mb-4">7. Account termination</h2>
        <p>We may suspend or terminate accounts that violate these terms or the permissions matrix.</p>

        <h2 class="heading-2 mt-8 mb-4">8. Contact</h2>
        <p>Questions: <a href="mailto:legal@pod.example.com">legal@pod.example.com</a>.</p>

        <p class="font-mono text-xs mt-12 text-ink-700">
            {{-- NOTE: this is v1.2 platform terms. Production launch requires review by counsel before public exposure. --}}
        </p>
    </article>
@endsection
