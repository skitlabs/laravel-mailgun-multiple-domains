<?php declare(strict_types=1);

namespace SkitLabs\LaravelMailGunMultipleDomains\Resolvers;

use Illuminate\Support\Facades\Config;
use SkitLabs\LaravelMailGunMultipleDomains\Contracts\MailGunSenderPropertiesResolver;

class MailGunSenderPropertiesFromServiceConfigResolver implements MailGunSenderPropertiesResolver
{
    /**
     * Get the mailgun domain for a given sender/from email-address.
     * This defaults to 'mg.{domain.tld}', but can be overwritten by
     * setting `services.mailgun.domains.{domain.tld}` to an array;
     * 'example.net' => ['domain' => 'custom-mg.example.net' => 'secret' => '...', 'endpoint' => '...']
     *
     * @note The domain and tld are transformed to lowercase.
     *
     * @return array{domain:string, secret:string, endpoint:string}
     */
    public function propertiesForDomain(string $domainName) : array
    {
        $domains = (array) Config::get('services.mailgun.domains', []);

        return [
            'domain' => (string) ($domains[$domainName]['domain'] ?? 'mg.' . $domainName),
            'secret' => (string) ($domains[$domainName]['secret'] ?? Config::get('services.mailgun.secret')),
            'endpoint' => (string) ($domains[$domainName]['endpoint'] ?? Config::get('services.mailgun.endpoint', 'api.mailgun.net')),
        ];
    }
}
