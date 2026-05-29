<?php

namespace App\Providers;

use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Listeners\SendCrudNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register CRUD event listeners
        Event::listen(UserCreated::class, [SendCrudNotification::class, 'handleCreated']);
        Event::listen(UserUpdated::class, [SendCrudNotification::class, 'handleUpdated']);
        Event::listen(UserDeleted::class, [SendCrudNotification::class, 'handleDeleted']);
    }
}
