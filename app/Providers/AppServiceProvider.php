<?php

namespace App\Providers;

use App\Models\Address;
use App\Models\DesignerProfile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrinterProviderProfile;
use App\Models\User;
use App\Policies\AddressPolicy;
use App\Policies\DesignerProfilePolicy;
use App\Policies\OrderItemPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PrinterProfilePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
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
    }
}
