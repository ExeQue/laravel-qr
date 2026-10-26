<?php

declare(strict_types=1);

namespace ExeQue\Qr\Registries;

use Closure;
use ExeQue\Qr\Registries\Concerns\IsRegistry;
use ExeQue\Qr\Writers\Contracts\Writer;
use ExeQue\Qr\Writers\Raster\Enums\Driver;
use ExeQue\Qr\Writers\Raster\Enums\ImageFormat;
use ExeQue\Qr\Writers\Raster\Gd\Writer as GdWriter;
use ExeQue\Qr\Writers\Raster\Imagick\Writer as RasterWriter;
use ExeQue\Qr\Writers\Raster\Options as RasterOptions;
use ExeQue\Qr\Writers\Svg\Options as SvgOptions;
use ExeQue\Qr\Writers\Svg\Writer as SvgWriter;
use Illuminate\Contracts\Container\Container;

/**
 * Output formats by name. A writer is registered as a closure that is given the
 * container and the code's writer options, and returns the writer:
 * `Qr::extend('pdf', fn (Container $app, array $options): Writer => new PdfWriter(...$options))`.
 */
final class WriterRegistry
{
    /** @use IsRegistry<Writer> */
    use IsRegistry;

    /**
     * Whether writers that can are asked for their fake: under `Qr::fake()`.
     */
    private bool $faking = false;

    public function kind(): string
    {
        return 'writer';
    }

    /**
     * Has writers that can stand in for themselves do so, for tests.
     */
    public function fake(bool $fake = true): void
    {
        $this->faking = $fake;
    }

    public function isFaking(): bool
    {
        return $this->faking;
    }

    protected function contract(): string
    {
        return Writer::class;
    }

    protected function provide(object $value): null
    {
        return null;
    }

    protected function builtIn(): array
    {
        return [
            'svg' => fn (Container $app, array $options): Writer => $app->make(SvgWriter::class, ['options' => new SvgOptions(...$options)]),
            'png' => self::raster(ImageFormat::Png),
            'jpeg' => self::raster(ImageFormat::Jpeg),
            'webp' => self::raster(ImageFormat::Webp),
        ];
    }

    protected function configKey(): string
    {
        return 'writers';
    }

    /**
     * The writer for `$name`, given the code's writer options.
     *
     * @param array<array-key, mixed> $options
     */
    public function make(string $name, array $options = []): Writer
    {
        $value = $this->entries()[$name] ?? null;

        return $value instanceof Closure ? $value($this->container, $options) : $this->get($name, $options);
    }

    /**
     * @return Closure(Container, array<array-key, mixed>): Writer
     */
    private static function raster(ImageFormat $format): Closure
    {
        return function (Container $app, array $options) use ($format): Writer {
            $driver = Driver::resolve($options['driver'] ?? null);
            unset($options['driver']);

            return $app->make($driver === Driver::Gd ? GdWriter::class : RasterWriter::class, [
                'options' => new RasterOptions(...[...$options, 'format' => $format]),
            ]);
        };
    }
}
