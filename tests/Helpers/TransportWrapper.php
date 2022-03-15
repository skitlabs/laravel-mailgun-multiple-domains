<?php declare(strict_types=1);

namespace SkitLabs\LaravelMailGunMultipleDomains\Tests\Helpers;

use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunApiTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

class TransportWrapper
{
    private TransportInterface $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    public function unwrap() : TransportInterface
    {
        return $this->transport;
    }

    public function getDomain() : ?string
    {
        return $this->getTransportValue('domain');
    }

    public function getKey() : ?string
    {

        return $this->getTransportValue('key');
    }

    public function getEndpoint() : ?string
    {
        if (! $this->transport instanceof MailgunApiTransport) {
            return null;
        }

        $endpoint = new \ReflectionMethod($this->transport, 'getEndpoint');
        $endpoint->setAccessible(true);

        return $endpoint->invoke($this->transport);
    }

    private function getTransportValue(string $name) : ?string
    {
        if (! $this->transport instanceof MailgunApiTransport) {
            return null;
        }

        $property = new \ReflectionProperty($this->transport, $name);
        $property->setAccessible(true);

        return $property->getValue($this->transport);
    }
}
