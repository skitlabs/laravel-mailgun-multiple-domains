<?php declare(strict_types=1);

namespace SkitLabs\LaravelMailGunMultipleDomains\Providers;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSending;
use SkitLabs\LaravelMailGunMultipleDomains\Contracts\MailGunSenderPropertiesResolver;
use SkitLabs\LaravelMailGunMultipleDomains\Listeners\ReconfigureMailGunOnMessageSending;
use SkitLabs\LaravelMailGunMultipleDomains\Resolvers\MailGunSenderPropertiesFromServiceConfigResolver;

class EventServiceProvider extends ServiceProvider
{
    /** @var array<array-key, mixed> */
    protected $listen = [
        MessageSending::class => [
            ReconfigureMailGunOnMessageSending::class,
        ],
    ];

    public function register()
    {
        parent::register();

        // Bind the default resolver, consumers can overwrite the implementation returned in their own ServiceProviders
        $this->app->bind(MailGunSenderPropertiesResolver::class, static function () : MailGunSenderPropertiesResolver {
            return new MailGunSenderPropertiesFromServiceConfigResolver();
        });

        $this->app->bind(ReconfigureMailGunOnMessageSending::class, static function (Application $app) : ReconfigureMailGunOnMessageSending {
            /** @var MailGunSenderPropertiesResolver $resolver */
            $resolver = $app->get(MailGunSenderPropertiesResolver::class);

            return new ReconfigureMailGunOnMessageSending($resolver);
        });
    }
}
