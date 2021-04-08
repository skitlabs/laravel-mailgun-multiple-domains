# Multiple Mailgun Domains in one Laravel app

Sending email through Mailgun is a breeze, when sending from _one domain_ only.   
For any additional domains, the _calling code_ needs to determine which mailer transport to use.   
This can be especially annoying when _the mailer to use depends on the sender_, which is often _set inside the mailable_.   

> Using this package, the _calling code_ is no longer concerned with the configured mailers.    

```php
<?php declare(strict_types=1);

// app/Mail/ImportantMessage.php
class ImportantMessage extends \Illuminate\Mail\Mailable
{
    public function build() : self
    {
        return $this
            ->subject('Important message')
            ->from('you@acme.tld') // The sender is often determined _inside_ the mailable
            ->view('...', []);
    }
}

/** @var \App\Mail\ImportantMessage $mailable */

// Without this package, the calling code has to select the right mailer 
\Illuminate\Support\Facades\Mail::mailer('mailgun-acme.tld')->to('j.doe@example.net')->queue($mailable);

// This package will handle the mailer configuration for you. So the above is as simple as;
\Illuminate\Support\Facades\Mail::to('j.doe@example.net')->queue($mailable);
```

## Installation

You can install the package via composer:

```bash
composer require skitlabs/laravel-mailgun-multiple-domains
```

## How it works

This package contains a listener that hooks into the `Illuminate\Mail\Events\MessageSending` event, which is dispatched _just before_ sending the e-mail.   
It then reconfigures the current `Transport`, based on the `from` domain in the message. This works for direct and queued messages alike, with no extra configuration!   

> Thanks to Laravel's auto-discovery (❤), no assembly required!


### Requirements
There are a few requirements for this to work;

* PHP 8.0, or 7.4
* Laravel >= 7.0
* Laravel needs to use `swiftmailer/swiftmailer` internally (default)

## Usage

If you've configured mailgun under `mg.{domain.tld}`, and your secret works for all domains you are sending from; you're ready to start sending e-mail! 👍     

Let's say you're sending a message as `sales@acme.app`. Just before sending the message, this package will set the mailgun domain to: `mg.acme.app`.
   
### What if I need to customize my settings, per domain?
Add the sending domains to your mailgun configuration in the key `domains`.

> If a domain is not specified, it defaults to `mg.{domain.tld}`.    
> If the `secret` or `endpoint` are not configured, these fallback to your configured global defaults.

```php
<?php declare(strict_types=1);

// config/services.php

return [
    // ... Other services

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'domains' => [
            'example.net' => [
                'domain' => 'custom-mg-domain.example.net',
                'secret' => 'overwrite-the-secret-or-null',
                'endpoint' => 'overwrite-the-endpoint-or-null',
            ],
            'awesome.app' => [
                'secret' => 'only-change-the-secret-for-this-domain',
            ],
        ],
    ],
];
```

### What if I need to customize how these settings are determined?
If the standard way of resolving sender properties is not suitable for your use-case, create a custom resolver that implements [MailGunSenderPropertiesResolver](src/Contracts/MailGunSenderPropertiesResolver.php).
See the [default implementation](src/Resolvers/MailGunSenderPropertiesFromServiceConfigResolver.php) for inspiration.

Once you have your own concrete implementation, overwrite the default bind in any of your service providers;

```php
<?php declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use SkitLabs\LaravelMailGunMultipleDomains\Contracts\MailGunSenderPropertiesResolver;

// app/Providers/AppServiceProvider.php
class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        // ...

        $this->app->bind(MailGunSenderPropertiesResolver::class, static function () : MailGunSenderPropertiesResolver {
            return new \Acme\CustomSenderPropertiesResolver();        
        });
    }
}
```

### What if my mailer has a different name?
Specify the name of your mailer, as the second argument, when instantiating `ReconfigureMailGunOnMessageSending`.

```php
<?php declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use SkitLabs\LaravelMailGunMultipleDomains\Contracts\MailGunSenderPropertiesResolver;
use SkitLabs\LaravelMailGunMultipleDomains\Listeners\ReconfigureMailGunOnMessageSending;

Config::set('mail.default', 'custom-mailer-name');
Config::set('mail.mailers.custom-mailer-name', [
    'transport' => 'mailgun',
]);

/** @var MailGunSenderPropertiesResolver $resolver */
$handler = new ReconfigureMailGunOnMessageSending($resolver, 'custom-mailer-name'); 
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
