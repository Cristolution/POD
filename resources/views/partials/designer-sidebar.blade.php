@php
    /** @var string|null $active */
    $links = [
        'Dashboard' => route('designer.dashboard'),
        'Orders' => route('designer.orders'),
        'Mappings' => route('designer.mappings'),
        'Edit profile' => route('designer.edit'),
    ];
@endphp

<x-layout.dashboard-sidebar :links="$links" :active="$active ?? request()->url()" />
