# Multiple Mailgun Domains in one Laravel app

Sending email through Mailgun is a breeze, as long as you're sending from one domain.   
For any additional domain, the calling code needs to manage the transport credentials.   
This is a lot of repeated code, and it's easy t


This package dynamically reconfigures the current `Transport`, based on the `Mailable` sender (`from`), without any change to your app code.   
This works for direct and queued messages alike, with no extra configuration 🚀

## Installation

You can install the package via composer:

```bash
composer require skitlabs/laravel-mailgun-multiple-domains
```

## Usage

This package contains a single event listener that modifies the mailer `Transport` on the fly.   
If you've configured mailgun under `mg.domain.tld`, and your secret works for all domains you are sending from; there is nothing more to do! 🎉    

The event listener will automatically change the mailgun domain, based on the `From` attribute.    
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
> If the `secret` or `endpoint` are not configured, these fallback to your configured defaults.    

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
