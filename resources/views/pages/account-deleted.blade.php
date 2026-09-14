<x-layouts.guest :title="__('account_deletion_landing_title')">
    <div class="mx-auto max-w-md text-center">
        <h1 class="text-2xl font-bold">{{ __('account_deletion_landing_heading') }}</h1>
        <p class="mt-4 text-sm">{{ __('account_deletion_landing_body') }}</p>
        <a href="{{ route('home') }}" class="mt-6 inline-block text-sm underline">
            {{ __('account_deletion_landing_back_home') }}
        </a>
    </div>
</x-layouts.guest>