@extends('layouts.app', ['title' => __('notifications_title')])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            __('breadcrumb_account') => route('account.dashboard'),
            __('breadcrumb_notifications') => route('account.notifications'),
        ]" />

        <h1 class="heading-1 mb-8">{{ __('notifications_heading') }}<span class="text-coral-500">.</span></h1>

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            <x-layout.account-sidebar />

            <div>
                @if ($notifications->isEmpty())
                    <div class="card-featured text-center">
                        <p class="font-mono">{{ __('notifications_empty') }}</p>
                    </div>
                @else
                    @if (session('status'))
                        <div class="card mb-6 border-coral-500">
                            <p class="font-display uppercase text-coral-500">{{ session('status') }}</p>
                        </div>
                    @endif

                    <ul class="space-y-3">
                        @foreach ($notifications as $notification)
                            @php $data = $notification->data; @endphp
                            <li class="card flex items-start gap-4 {{ $notification->read_at ? 'opacity-60' : 'border-coral-500' }}">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-display uppercase text-sm">
                                            {{ $data['event'] ?? __('notification_default_event') }}
                                        </span>
                                        @if (! $notification->read_at)
                                            <span class="badge badge-coral text-xs">{{ __('notifications_new_badge') }}</span>
                                        @endif
                                    </div>
                                    <div class="font-mono text-xs text-ink-700">
                                        {{ $notification->created_at?->diffForHumans() }}
                                    </div>
                                    @if (isset($data['order_id']))
                                        <div class="font-mono text-sm mt-2">
                                            {{ __('notification_order_label') }}
                                            <a href="{{ route('orders.confirmation', $data['order_id']) }}"
                                               class="text-coral-500 hover:underline">
                                                {{ substr($data['order_id'], 0, 8) }}
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex flex-col gap-2">
                                    @if (! $notification->read_at)
                                        <form method="POST" action="{{ route('account.notifications.read', $notification->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-secondary text-xs">{{ __('notifications_mark_read') }}</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('account.notifications.destroy', $notification->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-secondary text-xs">{{ __('notifications_delete') }}</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-6">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
