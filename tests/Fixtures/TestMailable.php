<?php declare(strict_types=1);

namespace SkitLabs\LaravelMailGunMultipleDomains\Tests\Fixtures;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestMailable extends Mailable
{
    use Queueable;
    use SerializesModels;

    public bool $wasCalled = false;

    public function build() : self
    {
        $this->wasCalled = true;

        return $this->html('Test');
    }
}
