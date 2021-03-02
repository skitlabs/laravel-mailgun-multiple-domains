<?php declare(strict_types=1);

namespace SkitLabs\LaravelMailGunMultipleDomains\Tests;

use SkitLabs\LaravelMailGunMultipleDomains\Providers\EventServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /** @return array<int, string> */
    protected function getPackageProviders($app) : array
    {
        return [
            EventServiceProvider::class,
        ];
    }
}
