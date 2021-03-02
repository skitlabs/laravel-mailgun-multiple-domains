# Multiple Mailgun Domains in one Laravel app

Sending email through Mailgun is a breeze, so long as you're sending from **one domain** only.   
For any additional domains, the _calling code_ needs to manage the transport credentials.   
This can lead to a lot of repeated code, helpers, or services, you need to copy between projects.   
As developers, this is one more action we need to remember; for every mailable in the system.   

## The solution

This package contains a listener that hooks into Laravel's `Illuminate\Mail\Events\MessageSending` event. This event is dispatched _just before_ sending the e-mail.   
The listener then dynamically reconfigures the current `Transport`, based on the `from` attribute. This works for direct and queued messages alike, with no extra configuration!

This package will only affect e-mails sent through the `mailgun`-mailer. Any other channels will be left untouched. 

> Thanks to Laravel's auto-discovery (❤), no assembly required!   

## Installation

You can install the package via composer:

```bash
composer require skitlabs/laravel-mailgun-multiple-domains
```

### Requirements
There are a few requirements for this to work;

* PHP 8.0, or 7.4
* Laravel >= 8.0 (although it will _likely_ work on any version >= 5.5)
* Laravel needs to use `swiftmailer/swiftmailer` internally (default)
* Your configured mailer is named `mailgun` (default)

## Usage

If you've configured mailgun under `mg.{domain.tld}`, and your secret works for all domains you are sending from; you're ready to start sending e-mail! 👍     

Let's say you're sending a message as `sales@acme.app`. Just before sending the message, this package will set the mailgun domain to: `mg.acme.app`.    

### What if I need to customize my settings, per domain?
If you have mailgun configured at custom subdomains, need multiple API secrets, or switch between endpoints;   
All you have to do is add the sending domains to your mailgun configuration in the key `domains`.       

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

> If a domain is not specified, it defaults to `mg.{domain.tld}`.    
> If the `secret` or `endpoint` are not configured, these fallback to your configured global defaults.    

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
