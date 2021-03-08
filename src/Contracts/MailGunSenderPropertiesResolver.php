<?php declare(strict_types=1);

namespace SkitLabs\LaravelMailGunMultipleDomains\Contracts;

interface MailGunSenderPropertiesResolver
{
    /**
     * Get the mailgun properties for a given sender domain.
     * The domain MUST default to `mg.{domain.tld}`, but may be overwritten
     * by the concrete implementation of choice.
     * The other parameters (secret/key, endpoint) MUST default to the
     * global mailgun service parameters.
     *
     * @param string $domainName The domain used in the 'from' header (like: example.net)
     *
     * @return array{domain:string, secret:string, endpoint:string}
     */
    public function propertiesForDomain(string $domainName) : array;
}
