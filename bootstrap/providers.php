<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\I18nServiceProvider;
use L5Swagger\L5SwaggerServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    I18nServiceProvider::class,
    L5SwaggerServiceProvider::class,
];
