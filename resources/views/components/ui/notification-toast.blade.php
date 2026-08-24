@props(['message'])

<div
    x-data="{ show: true }"
    x-show="show"
    x-init="setTimeout(() => show = false, 5000)"
    x-transition
    class="fixed top-6 right-6 z-50 badge-coral border-3 border-ink-800 px-6 py-3 shadow-none"
>
    {{ $message }}
</div>