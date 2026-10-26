<?php

declare(strict_types=1);

namespace Tests\Feature\Testing;

use BadMethodCallException;
use ExeQue\Qr\Builders\PendingQr;
use ExeQue\Qr\Exceptions\ContentTooLongException;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Registries\WriterRegistry;
use ExeQue\Qr\Rendering\Scene;
use ExeQue\Qr\Testing\QrFake;
use ExeQue\Qr\Writers\BufferedOutput;
use ExeQue\Qr\Writers\Contracts\Fakeable;
use ExeQue\Qr\Writers\Contracts\Output;
use ExeQue\Qr\Writers\Contracts\Writer;
use ExeQue\Qr\Writers\StreamFactory;
use Illuminate\Contracts\Container\Container;
use LogicException;
use PHPUnit\Framework\AssertionFailedError;

covers(QrFake::class, Qr::class, \ExeQue\Qr\Registries\WriterRegistry::class, \ExeQue\Qr\Writers\Svg\Writer::class, \ExeQue\Qr\Writers\Raster\Imagick\Writer::class);

pest()->group('builder');

const FAKE_URL = 'https://example.com/e/ABCD1234';

it('records codes instead of writing them', function (): void {
    Qr::fake();

    $png = Qr::make(FAKE_URL)->png(600);
    $svg = Qr::url(FAKE_URL)->margin(2)->svg();

    expect($png->mimeType())->toBe('image/png')
        ->and(getimagesizefromstring($png->contents()))->toMatchArray([0 => 256, 1 => 256])
        ->and((string) $svg)->toContain('<svg')
        ->and(Qr::make(FAKE_URL)->jpeg(100)->extension())->toBe('jpg')
        ->and(Qr::make(FAKE_URL)->webp(100)->mimeType())->toBe('image/webp');

    Qr::assertRendered(fn (PendingQr $qr) => $qr->content === FAKE_URL)
        ->assertRendered(fn (PendingQr $qr, string $format) => $format === 'svg')
        ->assertNotRendered(fn (PendingQr $qr) => $qr->content === 'https://other.test')
        ->assertRenderedCount(4)
        ->assertRenderedCount(1, fn (PendingQr $qr, string $format) => $format === 'png');
});

it('asserts nothing was rendered', function (): void {
    $fake = Qr::fake();

    Qr::assertNothingRendered();
    Qr::make(FAKE_URL)->svg();

    expect(fn () => $fake->assertNothingRendered())->toThrow(AssertionFailedError::class)
        ->and(fn () => $fake->assertRendered(fn (PendingQr $qr) => false))->toThrow(AssertionFailedError::class)
        ->and($fake->rendered())->toHaveCount(1);
});

it('still checks what it is given', function (): void {
    Qr::fake();

    expect(fn () => Qr::make(str_repeat('x', 8000))->svg())->toThrow(ContentTooLongException::class);
});

it('serves real codes, that scan', function (string $format): void {
    Qr::fake();

    expect(Qr::make(FAKE_URL)->format($format))->toDecodeAs('qr-fake');
})->with(['svg', 'png', 'jpeg', 'webp'])->group('scan');

it('leaves an application\'s own formats to their writers', function (): void {
    Qr::fake();
    Qr::extend('pdf', fn (Container $app, array $options): Writer => throw new LogicException('written'));

    expect(fn () => Qr::make(FAKE_URL)->format('pdf'))->toThrow(LogicException::class, 'written');
    Qr::assertRenderedCount(1);
});

it('leaves the facade alone without a fake', function (): void {
    expect(fn () => Qr::assertNothingRendered())->toThrow(BadMethodCallException::class);
});

it('asks an application\'s own writers for their fake when they have one', function (): void {
    Qr::fake();
    Qr::extend('note', fn (Container $app, array $options): Writer => new class () implements Fakeable, Writer {
        public function write(Scene $scene): Output
        {
            throw new LogicException('written');
        }

        public function fake(Scene $scene): Output
        {
            return new BufferedOutput(app(StreamFactory::class)->buffer()->write('fake note'), 'text/plain', 'txt');
        }
    });

    expect(Qr::make(FAKE_URL)->format('note')->contents())->toBe('fake note');
});

it('keeps fakes out of the cache', function (): void {
    config()->set('qr.cache.enabled', true);
    Qr::fake();
    Qr::make(FAKE_URL)->svg();

    app(WriterRegistry::class)->fake(false);
    app()->forgetScopedInstances();

    expect(Qr::make(FAKE_URL)->svg())->toDecodeAs(FAKE_URL);
})->group('scan');

it('keeps one fake when faked again, and keeps fakes apart from real codes', function (): void {
    $real = Qr::make(FAKE_URL)->svg()->contents();
    $fake = Qr::fake();
    Qr::make(FAKE_URL)->svg();

    expect(Qr::fake())->toBe($fake)
        ->and(Qr::make(FAKE_URL)->svg()->contents())->not->toBe($real);
    Qr::assertRenderedCount(2);
});
