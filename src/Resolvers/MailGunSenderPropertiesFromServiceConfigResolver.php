<?php declare(strict_types=1);

namespace SkitLabs\LaravelMailGunMultipleDomains\Resolvers;

use Illuminate\Support\Facades\Config;
use SkitLabs\LaravelMailGunMultipleDomains\Contracts\MailGunSenderPropertiesResolver;

class MailGunSenderPropertiesFromServiceConfigResolver implements MailGunSenderPropertiesResolver
{
    /** @inheritDoc */
    public function domainNameFrom($emailAddress) : string
    {
        // ['info@domain.tld' => 'Acme Info'] and ['info@domain.tld']
        if (is_array($emailAddress)) {
            $key = array_key_first($emailAddress);

            return is_string($key) ? $key : ($emailAddress[0] ?? '');
        }

        return $emailAddress;
    }

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
    public function propertiesForDomain(string $senderDomain) : array
    {
        $parts = explode('@', $senderDomain);
        $domain = mb_strtolower(array_pop($parts));

        $domains = (array) Config::get('services.mailgun.domains', []);

        return [
            'domain' => (string) ($domains[$domain]['domain'] ?? 'mg.' . $domain),
            'secret' => (string) ($domains[$domain]['secret'] ?? Config::get('services.mailgun.secret')),
            'endpoint' => (string) ($domains[$domain]['endpoint'] ?? Config::get('services.mailgun.endpoint', 'api.mailgun.net')),
        ];
    }
}
