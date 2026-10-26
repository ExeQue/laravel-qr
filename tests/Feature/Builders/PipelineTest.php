<?php

declare(strict_types=1);

namespace Tests\Feature\Builders;

use ExeQue\Qr\Builders\PendingQr;
use ExeQue\Qr\Builders\Pipeline;
use ExeQue\Qr\Events\QrCacheHit;
use ExeQue\Qr\Events\QrCacheMissed;
use ExeQue\Qr\Events\QrGuardApplied;
use ExeQue\Qr\Events\QrRendered;
use ExeQue\Qr\Events\QrRendering;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Registries\WriterRegistry;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Rendering\Scene;
use ExeQue\Qr\Shapes\Contracts\Versioned;
use ExeQue\Qr\Writers\BufferedOutput;
use ExeQue\Qr\Writers\Contracts\Output;
use ExeQue\Qr\Writers\Contracts\Writer;
use ExeQue\Qr\Writers\StreamFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use stdClass;

covers(
    Pipeline::class,
    \ExeQue\Qr\Monocle\Timeline::class,
    PendingQr::class,
    WriterRegistry::class,
    BufferedOutput::class,
    \ExeQue\Qr\Qr::class,
    QrRendering::class,
    QrRendered::class,
    QrCacheHit::class,
    QrCacheMissed::class,
    QrGuardApplied::class
);

pest()->group('builder');

const PIPELINE_URL = 'https://example.com/e/ABCD1234';

/**
 * A writer that counts how often it writes.
 */
final class CountingWriter implements Writer
{
    public static int $writes = 0;

    public function write(Scene $scene): Output
    {
        self::$writes++;

        return new BufferedOutput(app(StreamFactory::class)->buffer()->write("modules:{$scene->size}"), 'text/plain', 'txt');
    }
}

beforeEach(function (): void {
    CountingWriter::$writes = 0;
    Qr::extend('count', fn (Container $app, array $options): Writer => new CountingWriter());
});

describe('custom writers', function (): void {
    it('writes in a format of its own', function (): void {
        $output = Qr::make(PIPELINE_URL)->format('count');

        expect($output->contents())->toBe('modules:37')
            ->and($output->mimeType())->toBe('text/plain')
            ->and(Qr::writers()->names())->toContain('svg', 'png', 'jpeg', 'webp', 'count');
    });

    it('takes a writer class or instance as well as a closure', function (): void {
        Qr::writers()->register('counted', CountingWriter::class);

        expect(Qr::make(PIPELINE_URL)->format('counted')->contents())->toBe('modules:37')
            ->and(Qr::writers()->make('counted'))->toBeInstanceOf(CountingWriter::class)
            ->and(Qr::writers()->kind())->toBe('writer')
            ->and(function (): void {
                Qr::writers()->register('broken', stdClass::class);
                Qr::writers()->make('broken');
            })->toThrow(\ExeQue\Qr\Exceptions\RegistryException::class);
    });
});

describe('caching', function (): void {
    it('writes a code once per request, whatever the cache says', function (): void {
        Qr::make(PIPELINE_URL)->format('count');
        Qr::make(PIPELINE_URL)->format('count');

        expect(CountingWriter::$writes)->toBe(1);

        app()->forgetScopedInstances();
        Qr::make(PIPELINE_URL)->format('count');

        expect(CountingWriter::$writes)->toBe(2);
    });

    it('caches codes across requests when asked, as plain strings', function (): void {
        Qr::make(PIPELINE_URL)->cache()->format('count');
        app()->forgetScopedInstances();
        $again = Qr::make(PIPELINE_URL)->cache(60)->format('count');

        expect(CountingWriter::$writes)->toBe(1)
            ->and($again->contents())->toBe('modules:37')
            ->and($again->extension())->toBe('txt');
    });

    it('caches every code when the config says so, unless a code says not to', function (): void {
        config()->set('qr.cache.enabled', true);

        Qr::make(PIPELINE_URL)->format('count');
        app()->forgetScopedInstances();
        Qr::make(PIPELINE_URL)->format('count');
        app()->forgetScopedInstances();
        Qr::make(PIPELINE_URL)->withoutCache()->format('count');

        expect(CountingWriter::$writes)->toBe(2);
    });

    it('caches each look apart: style, format and options all count', function (): void {
        config()->set('qr.cache.enabled', true);

        Qr::make(PIPELINE_URL)->format('count');
        Qr::make(PIPELINE_URL)->margin(2)->format('count');
        Qr::make(PIPELINE_URL)->format('count', flavor: 'other');
        Qr::make(PIPELINE_URL.'?x')->format('count');

        expect(CountingWriter::$writes)->toBe(4);
    });

    it('keeps raster output apart by the configured driver', function (): void {
        $code = Qr::url('https://example.com/driver')->logo(dirname(__DIR__, 2).'/Fixtures/logos/ellipse.png', shape: 'alpha');

        config()->set('qr.raster.driver', 'gd');
        $gd = $code->png(200)->contents();
        config()->set('qr.raster.driver', 'imagick');

        expect($code->png(200)->contents())->not->toBe($gd);
    })->skip(! extension_loaded('imagick') || ! extension_loaded('gd'), 'Both drivers are needed.');

    it('forgets one code, or every code', function (): void {
        config()->set('qr.cache.enabled', true);
        $code = Qr::make(PIPELINE_URL)->margin(3);

        $code->format('count');
        Qr::forget(PIPELINE_URL, \ExeQue\Qr\Rendering\Style::default()->withMargin(3));
        app()->forgetScopedInstances();
        $code->format('count');

        Qr::flush();
        app()->forgetScopedInstances();
        $code->format('count');

        Qr::forget($code);
        app()->forgetScopedInstances();
        $code->format('count');

        expect(CountingWriter::$writes)->toBe(4);
    });

    it('does not cache a code it cannot know again', function (): void {
        config()->set('qr.cache.enabled', true);
        $closure = fn (ModuleContext $cell, ModuleStyle $style): ModuleStyle => $style;

        Qr::make(PIPELINE_URL)->filter($closure)->format('count');
        app()->forgetScopedInstances();
        Qr::make(PIPELINE_URL)->filter($closure)->format('count');
        app()->forgetScopedInstances();
        Qr::make(PIPELINE_URL)->filter($closure, name: 'same')->format('count');
        app()->forgetScopedInstances();
        Qr::make(PIPELINE_URL)->filter($closure, name: 'same')->format('count');

        expect(CountingWriter::$writes)->toBe(3);
    });

    it('keeps a code cached by a shape\'s version', function (): void {
        $shape = fn (int $version): object => new class ($version) implements \ExeQue\Qr\Shapes\Contracts\ModuleShape, Versioned {
            public function __construct(
                private int $version,
            ) {
            }

            public function draw(ModuleContext $cell): \ExeQue\Qr\Geometry\Path
            {
                return \ExeQue\Qr\Geometry\RoundedRect::path(-0.5, -0.5, 1, 1);
            }

            public function version(): int
            {
                return $this->version;
            }
        };

        expect(Qr::make(PIPELINE_URL)->modules($shape(1))->identity()['versions'][0])->toBe(1)
            ->and(Qr::make(PIPELINE_URL)->modules($shape(2))->identity()['versions'][0])->toBe(2);
    });

    it('reads only what it stored', function (): void {
        config()->set('qr.cache.enabled', true);
        $key = app(Pipeline::class)->key(Qr::make(PIPELINE_URL), 'count', []);
        Cache::put($key, 'not what was stored');

        expect(Qr::make(PIPELINE_URL)->format('count')->contents())->toBe('modules:37');
    });

    it('tells content apart that is not text', function (): void {
        config()->set('qr.cache.enabled', true);

        Qr::make("\xff\x01abc")->format('count');
        Qr::make("\xfe\x02xyz")->format('count');

        expect(CountingWriter::$writes)->toBe(2);
    });

    it('does not share a code between closures it cannot tell apart', function (): void {
        $red = fn (ModuleContext $cell, ModuleStyle $style): ModuleStyle => $style->withPaint(\ExeQue\Qr\Colors\Hex::make('#be123c'));
        $blue = fn (ModuleContext $cell, ModuleStyle $style): ModuleStyle => $style->withPaint(\ExeQue\Qr\Colors\Hex::make('#1e1b4b'));

        expect(Qr::make(PIPELINE_URL)->filter($red)->svg()->contents())->toContain('#be123c')
            ->and(Qr::make(PIPELINE_URL)->filter($blue)->svg()->contents())->toContain('#1e1b4b');
    });

    it('leaves the cache store alone when not caching', function (): void {
        config()->set('qr.cache.store', 'nowhere');

        expect(Qr::make(PIPELINE_URL)->format('count')->contents())->toBe('modules:37');
    });

    it('renders what it cannot cache, without caching it', function (): void {
        config()->set('qr.cache.enabled', true);
        $gradient = new class () implements \ExeQue\Qr\Gradients\Contracts\Gradient {
            public function colorAt(float $x, float $y): \ExeQue\Qr\Contracts\Color
            {
                return \ExeQue\Qr\Colors\Hex::make('#1e1b4b');
            }

            public function stops(): array
            {
                return [new \ExeQue\Qr\Gradients\ColorStop(\ExeQue\Qr\Colors\Hex::make('#1e1b4b'), 0.0)];
            }
        };
        $shape = new class (0.8) implements \ExeQue\Qr\Shapes\Contracts\ModuleShape {
            public function __construct(
                private float $size,
            ) {
            }

            public function draw(ModuleContext $cell): \ExeQue\Qr\Geometry\Path
            {
                return \ExeQue\Qr\Geometry\RoundedRect::path(-$this->size / 2, -$this->size / 2, $this->size, $this->size);
            }
        };

        expect(Qr::make(PIPELINE_URL)->paint($gradient)->isCacheable())->toBeFalse()
            ->and(Qr::make(PIPELINE_URL)->modules($shape)->isCacheable())->toBeFalse()
            ->and(Qr::make(PIPELINE_URL)->modules(new \ExeQue\Qr\Shapes\Modules\Dot(0.7))->isCacheable())->toBeTrue()
            ->and(Qr::make(PIPELINE_URL)->modules($shape)->format('count')->contents())->toBe('modules:37');
    });

    it('keeps a bounded number of codes per request', function (): void {
        foreach (range(1, 70) as $number) {
            Qr::make(PIPELINE_URL.$number)->format('count');
        }

        Qr::make(PIPELINE_URL.'70')->format('count');
        Qr::make(PIPELINE_URL.'1')->format('count');

        expect(CountingWriter::$writes)->toBe(71);
    });

    it('tells this request\'s own copy apart from a cache hit', function (): void {
        Event::fake([QrCacheHit::class, QrRendered::class]);

        Qr::make(PIPELINE_URL)->format('count');
        Qr::make(PIPELINE_URL)->format('count');

        Event::assertNotDispatched(QrCacheHit::class);
        Event::assertDispatched(QrRendered::class, fn (QrRendered $event): bool => $event->cached);
    });

    it('raises a generation once per forget, however many at once', function (): void {
        Qr::forget(Qr::make(PIPELINE_URL));
        Qr::forget(Qr::make(PIPELINE_URL));
        Qr::flush();

        expect(Cache::get('qr:generation'))->toBe(1)
            ->and(Cache::get('qr:generation:'.hash('xxh128', serialize(Qr::make(PIPELINE_URL)->identity()))))->toBe(2);
    });

})->group('cache');

describe('events', function (): void {
    it('tells what it does', function (): void {
        Event::fake();
        config()->set('qr.cache.enabled', true);

        Qr::make(PIPELINE_URL)->margin(1)->format('count');
        app()->forgetScopedInstances();
        Qr::make(PIPELINE_URL)->margin(1)->format('count');

        Event::assertDispatched(QrRendering::class, 2);
        Event::assertDispatched(QrCacheMissed::class, 1);
        Event::assertDispatched(QrCacheHit::class, 1);
        Event::assertDispatched(QrGuardApplied::class, fn (QrGuardApplied $event): bool => $event->adjustment->guard === 'margin');
        Event::assertDispatched(QrRendered::class, fn (QrRendered $event): bool => ! $event->cached
            && $event->version === 3
            && $event->modules === 29
            && array_keys($event->milliseconds) === ['encode', 'render', 'write', 'total']);
        Event::assertDispatched(QrRendered::class, fn (QrRendered $event): bool => $event->cached && array_keys($event->milliseconds) === ['total']);
    });

});
