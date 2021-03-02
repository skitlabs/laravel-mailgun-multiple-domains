<?php declare(strict_types=1);

namespace SkitLabs\LaravelMailGunMultipleDomains;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use SkitLabs\LaravelMailGunMultipleDomains\Providers\EventServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    public function register() : void
    {
        $this->app->register(EventServiceProvider::class);
    }
}
