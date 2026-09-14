<x-filament-panels::page>
    <div class="overflow-x-auto border-[3px] border-gray-800 bg-white">
        <table class="fi-ta-table w-full">
            <thead>
                <tr>
                    <th class="fi-ta-header-cell text-start px-4 py-2 uppercase tracking-wider text-xs border-b-[3px] border-gray-800">
                        Designer
                    </th>
                    <th class="fi-ta-header-cell text-start px-4 py-2 uppercase tracking-wider text-xs border-b-[3px] border-gray-800">
                        Email
                    </th>
                    <th class="fi-ta-header-cell text-start px-4 py-2 uppercase tracking-wider text-xs border-b-[3px] border-gray-800">
                        Profile
                    </th>
                    <th class="fi-ta-header-cell text-end px-4 py-2 uppercase tracking-wider text-xs border-b-[3px] border-gray-800">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($designers as $designer)
                    <tr wire:key="designer-{{ $designer->id }}">
                        <td class="fi-ta-cell px-4 py-3 text-sm">
                            {{ $designer->name }}
                        </td>
                        <td class="fi-ta-cell px-4 py-3 font-mono text-sm">
                            {{ $designer->email }}
                        </td>
                        <td class="fi-ta-cell px-4 py-3">
                            @if ($designer->designerProfile === null)
                                <span class="inline-block px-2 py-1 border-2 border-gray-800 bg-gray-200 text-gray-800 font-mono uppercase text-xs">
                                    No profile
                                </span>
                            @elseif (! $designer->designerProfile->is_verified)
                                <span class="inline-block px-2 py-1 border-2 border-gray-800 bg-orange-500 text-white font-mono uppercase text-xs">
                                    Unverified
                                </span>
                            @else
                                <span class="inline-block px-2 py-1 border-2 border-gray-800 bg-primary-100 text-primary-700 font-mono uppercase text-xs">
                                    Verified
                                </span>
                            @endif
                        </td>
                        <td class="fi-ta-cell px-4 py-3 text-end">
                            @if ($designer->designerProfile !== null && ! $designer->designerProfile->is_verified)
                                <button
                                    type="button"
                                    wire:click="verifyDesigner('{{ $designer->id }}')"
                                    wire:confirm="Verify {{ $designer->name }}?"
                                    class="px-3 py-1 border-2 border-gray-800 bg-primary-600 text-white font-mono uppercase text-xs hover:bg-primary-700"
                                >
                                    Verify
                                </button>
                            @else
                                <span class="font-mono text-xs text-gray-500">No action</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center p-12 font-mono text-gray-700">
                            No designers awaiting verification. The queue is empty.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 p-4 border-[3px] border-gray-800 bg-gray-50 font-mono text-sm text-gray-700">
        {{ $designers->count() }} {{ $designers->count() === 1 ? 'designer' : 'designers' }} awaiting review.
        Designers without a profile still appear here so you can follow up —
        they can't be verified until they finish their profile.
    </div>
</x-filament-panels::page>
