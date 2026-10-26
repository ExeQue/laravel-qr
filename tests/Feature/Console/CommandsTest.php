<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use ExeQue\Qr\Console\CheckCommand;
use ExeQue\Qr\Console\ClearCommand;
use ExeQue\Qr\Events\QrCacheHit;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Payloads\Text;
use Illuminate\Support\Facades\Event;
use stdClass;

covers(CheckCommand::class, ClearCommand::class);

pest()->group('console');

describe('clearing the cache', function (): void {
    it('makes every cached code stale', function (): void {
        config()->set('qr.cache.enabled', true);
        Event::fake([QrCacheHit::class]);

        Qr::make('https://example.com')->svg();
        $this->artisan('qr:clear')->expectsOutputToContain('Cached QR codes cleared.')->assertSuccessful();
        app()->forgetScopedInstances();
        Qr::make('https://example.com')->svg();

        Event::assertNotDispatched(QrCacheHit::class);
    });
})->group('cache');

describe('checking the config', function (): void {
    it('passes a sound config', function (): void {
        $this->artisan('qr:check')
            ->expectsOutputToContain('modules: square')
            ->expectsOutputToContain('payloads: wifi')
            ->expectsOutputToContain('decoders: stack')
            ->expectsOutputToContain('The QR code config is fine.')
            ->assertSuccessful();
    });

    it('names every broken entry', function (): void {
        config()->set('qr.modules', ['blob' => 'App\\Missing\\Blob']);
        config()->set('qr.payloads', ['note' => Text::class, 'bogus' => stdClass::class]);
        config()->set('qr.styles', ['loud' => ['modules' => ['shape' => 'nope']]]);
        config()->set('qr.scan_safety.on_violation', 'shout');

        $this->artisan('qr:check')
            ->expectsOutputToContain('modules: blob')
            ->expectsOutputToContain('payloads: bogus')
            ->expectsOutputToContain('styles: loud')
            ->expectsOutputToContain('settings: scan_safety')
            ->doesntExpectOutputToContain('payloads: note:')
            ->assertFailed();
    });

    it('names an entry that cannot be built at all', function (): void {
        config()->set('qr.filters', ['abstract' => \Illuminate\Support\Facades\Facade::class]);

        $this->artisan('qr:check')->expectsOutputToContain('filters: abstract')->assertFailed();
    });

});
