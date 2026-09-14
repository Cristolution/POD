<x-filament-panels::page>
    <div class="overflow-x-auto border-[3px] border-gray-800 bg-white">
        <table class="fi-ta-table w-full">
            <thead>
                <tr>
                    <th class="fi-ta-header-cell text-start px-4 py-2 uppercase tracking-wider text-xs border-b-[3px] border-gray-800">
                        Order
                    </th>
                    <th class="fi-ta-header-cell text-start px-4 py-2 uppercase tracking-wider text-xs border-b-[3px] border-gray-800">
                        Customer
                    </th>
                    <th class="fi-ta-header-cell text-start px-4 py-2 uppercase tracking-wider text-xs border-b-[3px] border-gray-800">
                        Method
                    </th>
                    <th class="fi-ta-header-cell text-start px-4 py-2 uppercase tracking-wider text-xs border-b-[3px] border-gray-800">
                        Uploaded
                    </th>
                    <th class="fi-ta-header-cell text-end px-4 py-2 uppercase tracking-wider text-xs border-b-[3px] border-gray-800">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr wire:key="payment-{{ $payment->id }}">
                        <td class="fi-ta-cell px-4 py-3 font-mono text-sm">
                            <a href="/admin/payments/{{ $payment->id }}" class="text-primary-700 underline">
                                #{{ $payment->order_id }}
                            </a>
                        </td>
                        <td class="fi-ta-cell px-4 py-3 text-sm">
                            {{ $payment->order?->customer?->name ?? '—' }}
                        </td>
                        <td class="fi-ta-cell px-4 py-3 text-sm">
                            {{ match ($payment->method) {
                                'cash_on_delivery' => 'Cash on delivery',
                                'bank_transfer' => 'Bank transfer',
                                'card' => 'Card',
                                default => $payment->method,
                            } }}
                        </td>
                        <td class="fi-ta-cell px-4 py-3 font-mono text-sm">
                            {{ $payment->created_at->diffForHumans() }}
                        </td>
                        <td class="fi-ta-cell px-4 py-3 text-end">
                            <div class="flex gap-2 justify-end">
                                <button
                                    type="button"
                                    wire:click="confirmPayment('{{ $payment->id }}')"
                                    wire:confirm="Confirm this payment?"
                                    class="px-3 py-1 border-2 border-gray-800 bg-primary-500 text-white font-mono uppercase text-xs hover:bg-primary-600"
                                >
                                    Confirm
                                </button>
                                <button
                                    type="button"
                                    wire:click="rejectPayment('{{ $payment->id }}')"
                                    wire:confirm="Reject this payment?"
                                    class="px-3 py-1 border-2 border-gray-800 bg-gray-800 text-white font-mono uppercase text-xs hover:bg-gray-900"
                                >
                                    Reject
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center p-12 font-mono text-gray-700">
                            No pending payments. The queue is empty.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 p-4 border-[3px] border-gray-800 bg-gray-50 font-mono text-sm text-gray-700">
        {{ $payments->count() }} pending {{ $payments->count() === 1 ? 'payment' : 'payments' }}.
        Confirming fires the customer notification; rejecting leaves the order
        in <code>pending</code> for manual follow-up.
    </div>
</x-filament-panels::page>
