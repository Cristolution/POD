@extends('layouts.app', ['title' => 'Account'])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Account' => route('account.dashboard'),
        ]" />

        <h1 class="heading-1 mb-8">Account<span class="text-coral-500">.</span></h1>

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            <x-layout.account-sidebar />

            <div class="space-y-8">
                {{-- Profile card --}}
                <div class="card">
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <h2 class="heading-3">Profile</h2>
                            <p class="font-mono text-xs text-ink-700 mt-1">
                                {{ $user->addresses_count }} addresses · {{ $user->orders_count }} orders
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label class="label" for="name">Name</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}"
                                   required class="input">
                            @error('name') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label" for="email">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
                                   required class="input">
                            @error('email') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label" for="phone">Phone</label>
                            <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}"
                                   class="input" autocomplete="tel">
                            @error('phone') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="btn-coral">Save profile</button>
                    </form>
                </div>

                {{-- Password card --}}
                <div class="card">
                    <h2 class="heading-3 mb-6">Change password</h2>

                    <form method="POST" action="{{ route('account.password.update') }}" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label class="label" for="current_password">Current password</label>
                            <input id="current_password" name="current_password" type="password" required
                                   class="input" autocomplete="current-password">
                            @error('current_password') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label" for="password">New password</label>
                            <input id="password" name="password" type="password" required
                                   class="input" autocomplete="new-password">
                            @error('password') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label" for="password_confirmation">Confirm new password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" required
                                   class="input" autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn-coral">Update password</button>
                    </form>
                </div>

                {{-- Recent orders --}}
                <div class="card">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="heading-3">Recent orders</h2>
                        <a href="{{ route('account.orders') }}" class="font-mono text-xs text-coral-500 hover:underline">
                            View all →
                        </a>
                    </div>

                    @if ($recentOrders->isEmpty())
                        <p class="font-mono text-sm text-ink-700">No orders yet.</p>
                    @else
                        <div class="table-wrap"><table class="table-pod">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentOrders as $order)
                                    <tr>
                                        <td>
                                            <a href="{{ route('orders.confirmation', $order) }}"
                                               class="font-mono text-coral-500 hover:underline">
                                                {{ substr($order->id, 0, 8) }}
                                            </a>
                                        </td>
                                        <td class="font-mono text-sm">
                                            {{ $order->created_at?->format('M j, Y') }}
                                        </td>
                                        <td>
                                            <span class="badge">{{ $order->status }}</span>
                                        </td>
                                        <td class="font-display text-right">
                                            ${{ number_format((float) $order->total_amount, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table></div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection