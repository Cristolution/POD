<x-filament-panels::page>
    {{-- Header filter bar — raw inputs wire-bound to $data for max v4 reliability. --}}
    <form
        wire:submit.prevent
        class="flex flex-wrap items-end gap-4 mb-6 p-4 border-3 border-gray-800 bg-gray-50"
    >
        <div class="flex flex-col flex-1 min-w-[160px]">
            <label for="filter-from" class="font-mono uppercase tracking-wider text-xs text-gray-700 mb-1">
                From
            </label>
            <input
                id="filter-from"
                type="date"
                wire:model.live="data.from"
                class="fi-input w-full px-4 py-2 bg-white text-gray-900 font-mono text-sm border-3 border-gray-800"
            />
        </div>

        <div class="flex flex-col flex-1 min-w-[160px]">
            <label for="filter-to" class="font-mono uppercase tracking-wider text-xs text-gray-700 mb-1">
                To
            </label>
            <input
                id="filter-to"
                type="date"
                wire:model.live="data.to"
                class="fi-input w-full px-4 py-2 bg-white text-gray-900 font-mono text-sm border-3 border-gray-800"
            />
        </div>

        <div class="flex items-end">
            <x-filament::button
                type="button"
                color="primary"
                wire:click="$refresh"
            >
                Refresh
            </x-filament::button>
        </div>

        <div class="flex items-end">
            <x-filament::button
                type="button"
                color="gray"
                wire:click="exportCsv"
            >
                Download CSV
            </x-filament::button>
        </div>
    </form>

    {{-- Chart widgets, one per opt-in report page. Rendered eagerly via
         <livewire> so the chart redraws when the date inputs above change. --}}
    @if (!empty($chartWidgets))
        <div class="grid grid-cols-1 gap-6 mb-6">
            @foreach ($chartWidgets as $widgetClass)
                @livewire($widgetClass, [
                    'from' => $data['from'] ?? null,
                    'to' => $data['to'] ?? null,
                ], key($widgetClass))
            @endforeach
        </div>
    @endif

    {{-- Report rows rendered as a brutalist table. --}}
    <div class="overflow-x-auto border-3 border-gray-800 bg-white">
        <table class="fi-ta-table w-full">
            <thead>
                <tr>
                    @if (count($rows) > 0)
                        @foreach (array_keys((array) $rows[0]) as $col)
                            <th class="fi-ta-header-cell text-start px-4 py-2 uppercase tracking-wider text-xs border-b-3 border-gray-800">
                                {{ ucwords(str_replace('_', ' ', (string) $col)) }}
                            </th>
                        @endforeach
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        @foreach ((array) $row as $cell)
                            @php $cell = match (true) {
                                is_array($cell), is_object($cell) => json_encode($cell),
                                default => (string) $cell,
                            }; @endphp
                            <td class="fi-ta-cell px-4 py-2 font-mono text-sm">
                                {{ $cell }}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="100" class="text-center p-12 font-mono text-gray-700">
                            No data for this period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>