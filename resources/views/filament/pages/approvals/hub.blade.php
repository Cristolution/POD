<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a
            href="/admin/approvals-payments"
            class="block p-6 border-[3px] border-gray-800 bg-white hover:bg-gray-50 transition-colors"
        >
            <div class="font-mono uppercase tracking-wider text-xs text-gray-700 mb-2">
                Pending payments
            </div>
            <div class="font-display text-5xl text-primary-700 leading-none mb-3">
                {{ $counts['pendingPayments'] }}
            </div>
            <div class="text-sm text-gray-700">
                Customer uploads awaiting admin confirmation.
            </div>
            <div class="mt-4 font-mono uppercase text-xs text-primary-700">
                Open queue →
            </div>
        </a>

        <a
            href="/admin/approvals-orders"
            class="block p-6 border-[3px] border-gray-800 bg-white hover:bg-gray-50 transition-colors"
        >
            <div class="font-mono uppercase tracking-wider text-xs text-gray-700 mb-2">
                Pending orders
            </div>
            <div class="font-display text-5xl text-primary-700 leading-none mb-3">
                {{ $counts['pendingOrders'] }}
            </div>
            <div class="text-sm text-gray-700">
                Orders awaiting the next status transition.
            </div>
            <div class="mt-4 font-mono uppercase text-xs text-primary-700">
                Open queue →
            </div>
        </a>

        <a
            href="/admin/approvals-designers"
            class="block p-6 border-[3px] border-gray-800 bg-white hover:bg-gray-50 transition-colors"
        >
            <div class="font-mono uppercase tracking-wider text-xs text-gray-700 mb-2">
                Designers to verify
            </div>
            <div class="font-display text-5xl text-primary-700 leading-none mb-3">
                {{ $counts['unverifiedDesigners'] }}
            </div>
            <div class="text-sm text-gray-700">
                Unverified designer accounts awaiting review.
            </div>
            <div class="mt-4 font-mono uppercase text-xs text-primary-700">
                Open queue →
            </div>
        </a>
    </div>

    <div class="mt-8 p-4 border-[3px] border-gray-800 bg-gray-50 font-mono text-sm text-gray-700">
        Each tile links to its dedicated queue. Actions on individual records
        (confirm, reject, advance, verify) live on those sub-pages so this
        hub stays a navigation surface, not a workbench.
    </div>
</x-filament-panels::page>
