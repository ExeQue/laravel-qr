<?php

declare(strict_types=1);

namespace ExeQue\Qr\Builders;

use BackedEnum;
use BadMethodCallException;
use DateInterval;
use DateTimeInterface;
use ExeQue\Qr\Builders\Concerns\BuildsStyle;
use ExeQue\Qr\Encoder\Encoder;
use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Encoder\Matrix;
use ExeQue\Qr\Exceptions\RegistryException;
use ExeQue\Qr\Factory;
use ExeQue\Qr\Filters\ClosureFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Payloads\Contracts\Payload;
use ExeQue\Qr\Payloads\Contracts\ProvidesQrContent;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\Renderer;
use ExeQue\Qr\Rendering\ScanSafety;
use ExeQue\Qr\Rendering\Scene;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\Versioned;
use ExeQue\Qr\Writers\Metadata;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use ReflectionClass;
use Stringable;

/**
 * A code being built: its content and style. Nothing is drawn until an output
 * method - `svg()`, `png()`, `jpeg()`, `webp()`, `matrix()`, `scene()` - is called;
 * every other call returns a changed copy.
 *
 * @implements Arrayable<string, mixed>
 */
final class PendingQr implements Arrayable, Htmlable, Stringable
{
    use BuildsStyle;
    use Conditionable;
    use Macroable {
        __call as macroCall;
    }
    use Tappable;

    /**
     * Null for the config's say, false not to cache, otherwise how long to.
     */
    private DateInterval|DateTimeInterface|int|false|null $cache = null;

    private int $minVersion = 1;

    public function __construct(
        public readonly string|Payload|ProvidesQrContent $content,
        Resolve $resolve,
        private readonly Factory $factory,
        private readonly Repository $config,
    ) {
        $this->resolve = $resolve;
        $this->style = Style::default();
        $this->metadata = new Metadata();
    }

    /**
     * @internal
     */
    public function fromBuilder(StyleBuilder $builder): self
    {
        $state = $builder->state();

        return $this->with(...$state);
    }

    /**
     * Macros first; then the content's own methods, so a payload is built on the code:
     * `Qr::wifi()->ssid('Wedding')->password('cake2026')->png()`.
     *
     * @param array<array-key, mixed> $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (! $this->content instanceof Payload || ! method_exists($this->content, $method)) {
            throw new BadMethodCallException(sprintf('Method %s::%s does not exist.', static::class, $method));
        }

        $result = $this->content->{$method}(...$parameters);

        return $result instanceof Payload ? clone($this, ['content' => $result]) : $result;
    }

    /**
     * The text the code holds.
     */
    public function text(): string
    {
        $content = $this->content instanceof ProvidesQrContent ? $this->content->qrContent() : $this->content;

        return $content instanceof Payload ? $content->toString() : $content;
    }

    public function matrix(): Matrix
    {
        return $this->factory->make(Encoder::class)->encode($this->text(), $this->level(), $this->minVersion, $this->mask);
    }

    /**
     * The code drawn, before any writer.
     */
    public function scene(): Scene
    {
        return $this->sceneOf($this->matrix());
    }

    /**
     * Caches the written code, for `$ttl` or the config's time; the config can also turn caching on for every code.
     */
    public function cache(DateInterval|DateTimeInterface|int|null $ttl = null): self
    {
        return clone($this, ['cache' => $ttl ?? (int) $this->config->get('qr.cache.ttl', 604800)]);
    }

    public function withoutCache(): self
    {
        return clone($this, ['cache' => false]);
    }

    /**
     * Makes every cached copy of this code stale, whatever its format and size.
     */
    public function forget(): void
    {
        $this->factory->make(Pipeline::class)->forget($this);
    }

    /**
     * @param mixed ...$options The SVG writer's options by name: size, pixels, precision, currentColor, symbol, ...
     */
    public function svg(mixed ...$options): Rendered
    {
        return $this->format('svg', ...[...$this->svgDefaults(), ...$options, 'metadata' => $this->metadataFor()]);
    }

    /**
     * @param mixed ...$options The raster writer's other options by name: dpi, quality.
     */
    public function png(?int $size = null, mixed ...$options): Rendered
    {
        return $this->raster('png', $size, $options);
    }

    /**
     * @param mixed ...$options
     */
    public function jpeg(?int $size = null, mixed ...$options): Rendered
    {
        return $this->raster('jpeg', $size, $options);
    }

    /**
     * @param mixed ...$options
     */
    public function webp(?int $size = null, mixed ...$options): Rendered
    {
        return $this->raster('webp', $size, $options);
    }

    /**
     * The code in any registered format - the built-in ones or one added with
     * `Qr::extend()` - with the writer's options by name.
     */
    public function format(string $name, mixed ...$options): Rendered
    {
        return $this->factory->make(Pipeline::class)->write($this, $name, $options, $this->cache);
    }

    /**
     * Everything that decides what the code looks like: what caching goes by.
     *
     * @return array<string, mixed>
     *
     * @internal
     */
    public function identity(): array
    {
        return [
            'content' => $this->text(),
            // A style that cannot be written down is known by this code alone.
            'style' => $this->storableStyle() ?? ['unstorable' => spl_object_id($this)],
            'errorCorrection' => $this->level()->value,
            'minVersion' => $this->minVersion,
            'mask' => $this->mask?->value,
            'metadata' => get_object_vars($this->metadataFor()),
            'scanSafety' => array_map(fn (mixed $value): mixed => $value instanceof BackedEnum ? $value->value : $value, get_object_vars($this->scanSafety ?? $this->configuredSafety())),
            'versions' => array_map(fn (object $shape): int => $shape instanceof Versioned ? $shape->version() : 1, $this->shapes()),
        ];
    }

    /**
     * Whether the code can be cached: everything that decides its look can be written down.
     * A closure filter without a name, a gradient the registry cannot store, or a shape with
     * settings that does not say what they are (Configurable) cannot.
     *
     * @internal
     */
    public function isCacheable(): bool
    {
        return array_all($this->style->filters(), fn (NamedFilter $filter): bool => ! $filter instanceof ClosureFilter || $filter->isCacheable())
            && $this->storableStyle() !== null
            && array_all($this->shapes(), fn (object $shape): bool => $shape instanceof Configurable || (new ReflectionClass($shape)->getConstructor()?->getNumberOfParameters() ?? 0) === 0);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function storableStyle(): ?array
    {
        try {
            return $this->style->toArray();
        } catch (RegistryException) {
            return null;
        }
    }

    /**
     * @return list<object>
     */
    private function shapes(): array
    {
        $shapes = [$this->style->modules->shape];

        foreach (Corner::cases() as $corner) {
            array_push($shapes, $this->style->eye($corner)->outer->shape, $this->style->eye($corner)->inner->shape);
        }

        return $shapes;
    }

    /**
     * The code drawn from an encoded matrix.
     *
     * @internal
     */
    public function sceneOf(Matrix $matrix): Scene
    {
        return $this->factory->make(Renderer::class)->render($matrix, $this->style, $this->scanSafety ?? $this->configuredSafety());
    }

    /**
     * The smallest version to use, so a design keeps one size.
     */
    public function minVersion(int $version): self
    {
        return clone($this, ['minVersion' => $version]);
    }

    /**
     * The code as inline SVG, for Blade.
     */
    public function toHtml(): string
    {
        return $this->svg()->toHtml();
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    /**
     * @param array<array-key, mixed> $options
     */
    private function raster(string $format, ?int $size, array $options): Rendered
    {
        return $this->format($format, ...[
            'dpi' => $this->config->get('qr.raster.dpi'),
            ...($format === 'png' ? ['compressionLevel' => (int) $this->config->get('qr.png.compression_level', 6)] : []),
            ...$options,
            'size' => $size ?? (int) $this->config->get('qr.defaults.size', 600),
            'metadata' => $this->metadataFor(),
        ]);
    }

    /**
     * The level set, else the config's - raised to High for a logo when the config says so.
     */
    private function level(): ErrorCorrection
    {
        if ($this->errorCorrection !== null) {
            return $this->errorCorrection;
        }

        if ($this->style->logo !== null && (bool) $this->config->get('qr.logos.auto_error_correction', true)) {
            return ErrorCorrection::High;
        }

        $default = $this->config->get('qr.defaults.error_correction', ErrorCorrection::Medium);

        return $default instanceof ErrorCorrection ? $default : ErrorCorrection::from((string) $default);
    }

    private function metadataFor(): Metadata
    {
        return new Metadata(...(array) $this->config->get('qr.metadata', []))->merge($this->metadata);
    }

    /**
     * @return array<string, mixed>
     */
    private function svgDefaults(): array
    {
        return array_filter([
            'precision' => $this->config->get('qr.svg.precision'),
            'xmlDeclaration' => $this->config->get('qr.svg.xml_declaration'),
        ], fn (mixed $value): bool => $value !== null);
    }

    private function configuredSafety(): ScanSafety
    {
        return ScanSafety::fromConfig((array) $this->config->get('qr.scan_safety', []));
    }
}
