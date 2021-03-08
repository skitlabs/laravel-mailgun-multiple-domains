<?php declare(strict_types=1);

namespace SkitLabs\LaravelMailGunMultipleDomains\Contracts;

interface MailGunSenderPropertiesResolver
{
    /**
     * Take an email-address and return the domain name (domain.tld).
     *
     * @note The email-address can either be passed as;
     * ['info@domain.tld' => 'Acme Info'], ['info@domain.tld'], 'info@domain.tld'
     *
     * @param array<string, string>|array<int, string>|string $emailAddress
     * @return string senderDomain
     */
    public function domainNameFrom($emailAddress) : string;

    /**
     * Get the mailgun properties for a given sender domain.
     * The domain MUST default to `mg.{domain.tld}`, but may be overwritten
     * by the concrete implementation of choice.
     * The other parameters (secret/key, endpoint) MUST default to the
     * global mailgun service parameters.
     *
     * @return array{domain:string, secret:string, endpoint:string}
     */
    public function propertiesForDomain(string $senderDomain) : array;
}
