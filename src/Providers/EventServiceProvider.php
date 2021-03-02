<?php declare(strict_types=1);

namespace SkitLabs\LaravelMailGunMultipleDomains\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSending;
use SkitLabs\LaravelMailGunMultipleDomains\Listeners\ReconfigureMailGunOnMessageSending;

class EventServiceProvider extends ServiceProvider
{
    /** @var array<array-key, mixed> */
    protected $listen = [
        MessageSending::class => [
            ReconfigureMailGunOnMessageSending::class,
        ],
    ];
}
