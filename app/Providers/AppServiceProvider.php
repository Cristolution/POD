<?php

namespace App\Providers;

use App\Models\Address;
use App\Models\DeliveryCompany;
use App\Models\DesignerProfile;
use App\Models\Media;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PrinterProviderProfile;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\User;
use App\Policies\AddressPolicy;
use App\Policies\DeliveryCompanyPolicy;
use App\Policies\DesignerProfilePolicy;
use App\Policies\MediaPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\OrderItemPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PrinterProfilePolicy;
use App\Policies\SettingPolicy;
use App\Policies\ShipmentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(DesignerProfile::class, DesignerProfilePolicy::class);
        Gate::policy(PrinterProviderProfile::class, PrinterProfilePolicy::class);
        Gate::policy(Address::class, AddressPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(OrderItem::class, OrderItemPolicy::class);
        Gate::policy(DeliveryCompany::class, DeliveryCompanyPolicy::class);
        Gate::policy(Shipment::class, ShipmentPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Media::class, MediaPolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);

        $this->registerRateLimiters();
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();

            return $user
                ? Limit::perMinute(120)->by($user->id)
                : Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip())->response(function (Request $request) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Too many auth attempts.'], 429);
                }

                return back()->withErrors(['email' => 'Too many attempts. Please try again in a minute.']);
            });
        });

        RateLimiter::for('uploads', function (Request $request) {
            $user = $request->user();

            return $user
                ? Limit::perMinute(30)->by($user->id)
                : Limit::perMinute(5)->by($request->ip());
        });
    }
}
