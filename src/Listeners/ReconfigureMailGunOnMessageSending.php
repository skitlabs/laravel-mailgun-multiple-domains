<?php declare(strict_types=1);

namespace SkitLabs\LaravelMailGunMultipleDomains\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\MailgunTransport;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use SkitLabs\LaravelMailGunMultipleDomains\Contracts\MailGunSenderPropertiesResolver;

class ReconfigureMailGunOnMessageSending
{
    private MailGunSenderPropertiesResolver $resolver;
    private string $mailer;

    public function __construct(MailGunSenderPropertiesResolver $resolver, string $mailer = 'mailgun')
    {
        $this->resolver = $resolver;
        $this->mailer = $mailer;
    }

    /**
     * Just before sending an email, laravel dispatches the `MessageSending` event.
     * Use this moment to update the current mail Transport configuration.
     *
     * As far as I know, there is no easier way to use multiple Mailgun domains in laravel,
     * that do not depend on the calling code (Mailables / Controllers) to set this configuration.
     * Seeing as devs are likely to forget, depending on a system event seems much more friendly.
     */
    public function handle(MessageSending $event) : void
    {
        if (! $this->isUsingMailgun()) {
            return;
        }

        ['domain' => $domain, 'secret' => $secret, 'endpoint' => $endpoint] = $this->resolver->propertiesForDomain(
            $this->domainNameFrom($event->message->getFrom()),
        );

        $this->configureSender($domain, $secret, $endpoint);
    }

    /** Test if the configured mailer matches the one this handler is listening for */
    private function isUsingMailgun() : bool
    {
        $mailer = (string) Config::get('mail.default', '');

        return $mailer === $this->mailer;
    }

    /**
     * Take an email-address (j.doe@example.net) and return the domain name (example.net).
     *
     * @note The email-address can either be passed as;
     * ['info@domain.tld' => 'Acme Info'], ['info@domain.tld'], 'info@domain.tld'
     * @note The domain (example.net) is always returned as lower-case.
     *
     * @param array<string, string>|array<int, string>|string|mixed $emailAddress
     */
    private function domainNameFrom($emailAddress) : string
    {
        // ['info@domain.tld' => 'Acme Info'] and ['info@domain.tld']
        if (is_array($emailAddress)) {
            $key = array_key_first($emailAddress);

            $sender = is_string($key) ? $key : (string) ($emailAddress[0] ?? '');
        } else {
            $sender = (string) $emailAddress;
        }

        $parts = explode('@', $sender);

        return mb_strtolower(array_pop($parts));
    }

    /**
     * Since the event is dispatched from the Mailer self, we can't just 'forget' the shared
     * instance, set our configuration, and proceed as normal. This would be possible in the
     * calling code (App::forgetInstance('mailer') and Mail::clearResolvedInstance('mailer')).
     * Another option would be to `App::get(MailManager::class)`, purge and re-resolve.
     *
     * Through the event, we need to reconfigure the current instance of the mailer. This
     * makes the assumption that Laravel is using the `swift-mailer` package internally.
     *
     * Either replace the current swift-mailer instance (setSwiftMailer), or specifically
     * patch the `MailgunTransport` when returned.
     *
     * @see MailManager::createSwiftMailer
     */
    private function configureSender(string $domain, string $secret, string $endpoint) : void
    {
        $transport = Mail::mailer($this->mailer)->getSwiftMailer()->getTransport();

        if (! $transport instanceof MailgunTransport) {
            throw new \RuntimeException('Non mailgun transport returned!');
        }

        $transport->setDomain($domain);
        $transport->setKey($secret);
        $transport->setEndpoint($endpoint);
    }
}
