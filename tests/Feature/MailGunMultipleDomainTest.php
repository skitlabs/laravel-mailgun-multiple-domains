<?php declare(strict_types=1);

namespace SkitLabs\LaravelMailGunMultipleDomains\Tests\Feature;

use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Mail\Events\MessageSending;
use SkitLabs\LaravelMailGunMultipleDomains\Tests\Helpers\TransportWrapper;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunApiTransport;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use SkitLabs\LaravelMailGunMultipleDomains\Contracts\MailGunSenderPropertiesResolver;
use SkitLabs\LaravelMailGunMultipleDomains\Listeners\ReconfigureMailGunOnMessageSending;
use SkitLabs\LaravelMailGunMultipleDomains\Resolvers\MailGunSenderPropertiesFromServiceConfigResolver;
use SkitLabs\LaravelMailGunMultipleDomains\Tests\Fixtures\TestMailable;
use SkitLabs\LaravelMailGunMultipleDomains\Tests\TestCase;

class MailGunMultipleDomainTest extends TestCase
{
    public function setUp() : void
    {
        parent::setUp();

        // Assert that our Listener is set up to receive the event
        /** @var Dispatcher $dispatcher */
        $dispatcher = resolve(Dispatcher::class);

        $isConfigured = collect($dispatcher->getListeners(MessageSending::class))
            ->filter(static function (\Closure $wrapper) : bool {
                $listener = (new \ReflectionFunction($wrapper))->getStaticVariables()['listener'] ?? null;

                return $listener === ReconfigureMailGunOnMessageSending::class;
            })->count() === 1;

        $this->assertTrue($isConfigured);

        // Invoke the mailer once, and verify that 'phpunit.xml' env is set
        $transport = $this->getTransport();

        if ($transport->unwrap() instanceof MailgunApiTransport) {
            $this->assertEquals('foo.bar.baz', $transport->getDomain());
            $this->assertEquals('qwe-asd-zxc', $transport->getKey());
            $this->assertEquals('api.mailgun.net', $transport->getEndpoint());
        }
    }

    /** @test */
    public function does_not_modify_transport_if_not_using_mailgun() : void
    {
        Config::set('mail.default', 'smtp');

        $originalTransport = clone Mail::mailer()->getSymfonyTransport();

        $this->sendFakedEmail('foo@example.net', function () use ($originalTransport) : void {
            $transport = $this->getTransport()->unwrap();

            $this->assertNotInstanceOf(MailgunApiTransport::class, $transport);
            $this->assertEquals($originalTransport, $transport);
        });
    }

    /** @test */
    public function dynamically_configures_sending_domain() : void
    {
        Config::set('services.mailgun.domains', [
            'example.net' => [
                'domain' => 'custom-mg.example.net',
                'secret' => '123-456-789',
                'endpoint' => 'api.eu.mailgun.net',
            ],
        ]);

        $this->sendFakedEmail('foo@example.net', function () : void {
            $transport = $this->getTransport();

            $this->assertInstanceOf(MailgunApiTransport::class, $transport->unwrap());
            $this->assertEquals('custom-mg.example.net', $transport->getDomain());
            $this->assertEquals('123-456-789', $transport->getKey());
            $this->assertEquals('api.eu.mailgun.net', $transport->getEndpoint());
        });
    }

    /** @test */
    public function can_handle_multiple_messages_in_a_row() : void
    {
        Config::set('services.mailgun.domains', [
            'example.net' => [
                'domain' => 'custom-mg.example.net',
                'secret' => '123-456-789',
                'endpoint' => 'api.eu.mailgun.net',
            ],
            'awesome.app' => [
                'domain' => 'mg-marketing.awesome.app',
                'secret' => 'abc-def-ghi',
                'endpoint' => 'api.mailgun.net',
            ],
        ]);

        $this->sendFakedEmail('foo@example.net', function () : void {
            $transport = $this->getTransport();

            $this->assertInstanceOf(MailgunApiTransport::class, $transport->unwrap());
            $this->assertEquals('custom-mg.example.net', $transport->getDomain());
            $this->assertEquals('123-456-789', $transport->getKey());
            $this->assertEquals('api.eu.mailgun.net', $transport->getEndpoint());
        });

        $this->resetListeners();

        $this->sendFakedEmail('marketing@awesome.app', function () : void {
            $transport = $this->getTransport();

            $this->assertInstanceOf(MailgunApiTransport::class, $transport->unwrap());
            $this->assertEquals('mg-marketing.awesome.app', $transport->getDomain());
            $this->assertEquals('abc-def-ghi', $transport->getKey());
            $this->assertEquals('api.mailgun.net', $transport->getEndpoint());
        });
    }

    /** @test */
    public function configuration_has_fallback() : void
    {
        Config::set('services.mailgun.domains', []);

        $this->sendFakedEmail('foo@example.net', function () : void {
            $transport = $this->getTransport();

            $this->assertInstanceOf(MailgunApiTransport::class, $transport->unwrap());
            $this->assertEquals('mg.example.net', $transport->getDomain());
            $this->assertEquals('qwe-asd-zxc', $transport->getKey());
            $this->assertEquals('api.mailgun.net', $transport->getEndpoint());
        });
    }

    /** @test */
    public function uses_default_resolver() : void
    {
        $this->assertTrue($this->app->has(MailGunSenderPropertiesResolver::class));

        $this->assertEquals(
            MailGunSenderPropertiesFromServiceConfigResolver::class,
            get_class($this->app->get(MailGunSenderPropertiesResolver::class)),
        );
    }

    /** @test */
    public function consumer_can_override_resolver() : void
    {
        $resolver = new class implements MailGunSenderPropertiesResolver {
            public bool $propertiesResolved = false;

            public function propertiesForDomain(string $senderDomain): array
            {
                $this->propertiesResolved = $senderDomain === 'bar.baz';

                return [
                    'domain' => 'mg.bar.baz',
                    'secret' => '123123-456456',
                    'endpoint' => 'custom-endpoint.mailgun.net',
                ];
            }
        };

        $this->app->bind(MailGunSenderPropertiesResolver::class, static function () use ($resolver) : MailGunSenderPropertiesResolver {
            return $resolver;
        });

        $this->assertFalse($resolver->propertiesResolved);

        $this->sendFakedEmail('foo@bar.baz', static fn () => false);

        $this->assertTrue($resolver->propertiesResolved);
    }

    /**
     * Make sure the default properties do not match our assert.
     * So the test will only pass if the event handler kicks in
     * and reconfigures the transport. Meaning that $mailer,
     * assigned to the handler has to match the one in Config.
     *
     * @see ReconfigureMailGunOnMessageSending::isUsingMailgun()
     *
     * @test
     */
    public function consumer_can_use_custom_mailer_name() : void
    {
        Config::set('mail.default', 'custom-mailer-name');
        Config::set('mail.mailers.custom-mailer-name', [
            'transport' => 'mailgun',
        ]);
        Config::set('services.mailgun', [
            'domain' => 'foo.bar.baz',
            'secret' => 'hunter42',
            'endpoint' => 'api.mailgun.net',
            'domains' => [
                'example.com' => [
                    'domain' => 'mg.example.com',
                    'secret' => 'super-duper-secret',
                    'endpoint' => 'non-standard.mailgun.net',
                ],
            ],
        ]);

        $this->app->bind(ReconfigureMailGunOnMessageSending::class, static function (Application $app) : ReconfigureMailGunOnMessageSending {
            /** @var MailGunSenderPropertiesResolver $resolver */
            $resolver = $app->get(MailGunSenderPropertiesResolver::class);

            return new ReconfigureMailGunOnMessageSending($resolver, 'custom-mailer-name');
        });

        $this->sendFakedEmail('test@example.com', function () : void {
            $transport = $this->getTransport();

            $this->assertInstanceOf(MailgunApiTransport::class, $transport->unwrap());
            $this->assertEquals('mg.example.com', $transport->getDomain());
            $this->assertEquals('super-duper-secret', $transport->getKey());
            $this->assertEquals('non-standard.mailgun.net', $transport->getEndpoint());
        });
    }

    private function getTransport(?string $name = null) : TransportWrapper
    {
        return new TransportWrapper(Mail::mailer($name)->getSymfonyTransport());
    }

    private function sendFakedEmail(string $from, \Closure $closure) : void
    {
        /** @var Dispatcher $dispatcher */
        $dispatcher = resolve(Dispatcher::class);

        // Assertions need to be made _after_ our reconfigure event, so append them here
        $dispatcher->listen(MessageSending::class, $closure);

        // Prevent actually hitting the mailgun servers
        $dispatcher->listen(MessageSending::class, static fn() => false);

        $mailable = (new TestMailable())->from($from);

        Mail::to('john.doe@example.net')->send($mailable);

        $this->assertTrue($mailable->wasCalled);
    }

    private function resetListeners() : void
    {
        $dispatcher = resolve(Dispatcher::class);

        // Forget about any previous listeners
        $dispatcher->forget(MessageSending::class);

        // Set up this project listener
        $dispatcher->listen(MessageSending::class, ReconfigureMailGunOnMessageSending::class);
    }
}
