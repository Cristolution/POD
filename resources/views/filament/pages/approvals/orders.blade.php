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
                        Current status
                    </th>
                    <th class="fi-ta-header-cell text-end px-4 py-2 uppercase tracking-wider text-xs border-b-[3px] border-gray-800">
                        Total
                    </th>
                    <th class="fi-ta-header-cell text-end px-4 py-2 uppercase tracking-wider text-xs border-b-[3px] border-gray-800">
                        Next step
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    @php
                        $nextLabel = match ($order->status) {
                            'pending' => 'Mark paid',
                            'paid' => 'Mark processing',
                            'processing' => 'Mark shipped',
                            'shipped' => 'Mark delivered',
                            default => '—',
                        };
                    @endphp
                    <tr wire:key="order-{{ $order->id }}">
                        <td class="fi-ta-cell px-4 py-3 font-mono text-sm">
                            <a href="/admin/orders/{{ $order->id }}" class="text-primary-700 underline">
                                #{{ $order->id }}
                            </a>
                        </td>
                        <td class="fi-ta-cell px-4 py-3 text-sm">
                            {{ $order->customer?->name ?? '—' }}
                        </td>
                        <td class="fi-ta-cell px-4 py-3">
                            <span class="inline-block px-2 py-1 border-2 border-gray-800 font-mono uppercase text-xs
                                {{ match ($order->status) {
                                    'pending' => 'bg-gray-100 text-gray-800',
                                    'paid' => 'bg-gray-200 text-gray-800',
                                    'processing' => 'bg-orange-500 text-white',
                                    'shipped' => 'bg-primary-100 text-primary-700',
                                    default => 'bg-gray-100 text-gray-800',
                                } }}">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td class="fi-ta-cell px-4 py-3 text-end font-mono text-sm">
                            ${{ number_format((float) $order->total_amount, 2) }}
                        </td>
                        <td class="fi-ta-cell px-4 py-3 text-end">
                            <button
                                type="button"
                                wire:click="advanceOrder('{{ $order->id }}')"
                                wire:confirm="Advance order #{{ $order->id }} from '{{ $order->status }}' to next state?"
                                class="px-3 py-1 border-2 border-gray-800 bg-primary-600 text-white font-mono uppercase text-xs hover:bg-primary-700"
                            >
                                {{ $nextLabel }} →
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center p-12 font-mono text-gray-700">
                            No open orders. The queue is empty.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 p-4 border-[3px] border-gray-800 bg-gray-50 font-mono text-sm text-gray-700">
        {{ $orders->count() }} open {{ $orders->count() === 1 ? 'order' : 'orders' }}.
        Advance fires the matching MarkOrder*Action, including any customer
        notifications. Cancellations remain on the order view page.
    </div>
</x-filament-panels::page>
