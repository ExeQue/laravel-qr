<?php

declare(strict_types=1);

namespace ExeQue\Qr\Builders\Concerns;

use Closure;
use ExeQue\Qr\Builders\BackgroundBuilder;
use ExeQue\Qr\Builders\EyeBuilder;
use ExeQue\Qr\Builders\EyesBuilder;
use ExeQue\Qr\Builders\LogoBuilder;
use ExeQue\Qr\Builders\ModulesBuilder;
use ExeQue\Qr\Builders\Resolve;
use ExeQue\Qr\Builders\StyleBuilder;
use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Encoder\Enums\Mask;
use ExeQue\Qr\Exceptions\InvalidOptionException;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Filters\Contracts\ProvidesQrFilter;
use ExeQue\Qr\Logos\Contracts\ProvidesQrLogo;
use ExeQue\Qr\Logos\Logo;
use ExeQue\Qr\Qr;
use ExeQue\Qr\Rendering\Contracts\ProvidesQrStyle;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Rendering\ScanSafety;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrEye;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrInnerEye;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrModule;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrOuterEye;
use ExeQue\Qr\Support\Hydrate;
use ExeQue\Qr\Writers\Metadata;
use SplFileInfo;

/**
 * Every styling call a code or a reusable style takes. Each returns a changed copy,
 * so a builder can be kept and built on safely.
 *
 * @internal
 */
trait BuildsStyle
{
    protected Style $style;

    protected Resolve $resolve;

    /**
     * Null until set: the config's default, raised to High for a logo.
     */
    protected ?ErrorCorrection $errorCorrection = null;

    protected int $minVersion = 1;

    protected ?Mask $mask = null;

    protected Metadata $metadata;

    protected ?ScanSafety $scanSafety = null;

    /**
     * The module shape: an instance, a provider, a class name or a registered
     * name with its options - or a closure, given the modules' builder.
     *
     * @param ModuleShape|ProvidesQrModule|Closure(ModulesBuilder): ModulesBuilder|string $shape
     */
    public function modules(ModuleShape|ProvidesQrModule|Closure|string $shape, mixed ...$options): static
    {
        if ($shape instanceof Closure) {
            return $this->with(style: $this->style->withModules($shape(new ModulesBuilder($this->style->modules, $this->resolve))->toModuleStyle()));
        }

        return $this->with(style: $this->style->withModules($this->style->modules->withShape($this->resolve->module($shape, $options))));
    }

    /**
     * The paint of the modules, and of every eye without one of its own: any
     * color form, a gradient, or a stored gradient's array.
     *
     * @param Paint|string|array<string, mixed> $paint
     */
    public function paint(Paint|string|array $paint): static
    {
        return $this->with(style: $this->style->withPaint(Hydrate::paint($paint)));
    }

    /**
     * Any color form or a gradient; null for none - or a closure, given the background's builder.
     *
     * @param Paint|string|array<string, mixed>|Closure(BackgroundBuilder): BackgroundBuilder|null $background
     */
    public function background(Paint|string|array|Closure|null $background): static
    {
        $paint = match (true) {
            $background instanceof Closure => $background(new BackgroundBuilder($this->style->background))->toPaint(),
            $background === null => null,
            default => Hydrate::paint($background),
        };

        return $this->with(style: $this->style->withBackground($paint));
    }

    /**
     * The eyes of every corner: a whole eye (an outer and an inner together), or
     * the outer and inner apart - or a closure, given the eyes' builder.
     *
     * @param ProvidesQrEye|Closure(EyesBuilder): EyesBuilder|string|null $eye
     */
    public function eyes(
        ProvidesQrEye|Closure|string|null $eye = null,
        OuterEyeShape|ProvidesQrOuterEye|string|null $outer = null,
        InnerEyeShape|ProvidesQrInnerEye|string|null $inner = null,
    ): static {
        if ($eye instanceof Closure) {
            return $this->with(style: $eye(new EyesBuilder($this->style, $this->resolve))->toStyle());
        }

        $builder = new EyesBuilder($this->style, $this->resolve);

        if ($eye !== null) {
            $whole = $this->resolve->eye($eye);
            $builder = $builder->outer($whole->qrOuterEye())->inner($whole->qrInnerEye());
        }

        if ($outer !== null) {
            $builder = $builder->outer($outer);
        }

        if ($inner !== null) {
            $builder = $builder->inner($inner);
        }

        return $this->with(style: $builder->toStyle());
    }

    /**
     * One corner's eye: its parts, a rotation in degrees and a paint - or a closure, given that eye's builder.
     *
     * @param ProvidesQrEye|Closure(EyeBuilder): EyeBuilder|string|null $eye
     * @param Paint|string|array<string, mixed>|null $paint
     */
    public function eye(
        Corner|string $corner,
        ProvidesQrEye|Closure|string|null $eye = null,
        OuterEyeShape|ProvidesQrOuterEye|string|null $outer = null,
        InnerEyeShape|ProvidesQrInnerEye|string|null $inner = null,
        ?float $rotation = null,
        Paint|string|array|null $paint = null,
    ): static {
        $corner = is_string($corner) ? Corner::from($corner) : $corner;
        $builder = new EyeBuilder($this->style->eye($corner), $this->resolve);

        $builder = match (true) {
            $eye instanceof Closure => $eye($builder),
            default => $builder
                // Fluent when() without a callback: the proxy it returns is beyond PHPStan's inference.
                // @phpstan-ignore argument.templateType
                ->when($eye !== null)->whole($eye)
                ->when($outer !== null)->outer($outer)
                ->when($inner !== null)->inner($inner)
                ->when($rotation !== null)->rotate((float) $rotation)
                ->when($paint !== null)->paint($paint),
        };

        return $this->with(style: $this->style->withEye($corner, $builder->toEyeStyle()));
    }

    /**
     * The outer eye of every corner.
     */
    public function outer(OuterEyeShape|ProvidesQrOuterEye|string $eye, mixed ...$options): static
    {
        return $this->with(style: new EyesBuilder($this->style, $this->resolve)->outer($eye, ...$options)->toStyle());
    }

    /**
     * The inner eye of every corner.
     */
    public function inner(InnerEyeShape|ProvidesQrInnerEye|string $eye, mixed ...$options): static
    {
        return $this->with(style: new EyesBuilder($this->style, $this->resolve)->inner($eye, ...$options)->toStyle());
    }

    /**
     * Turns the top-right and bottom-left eyes so a corner drawn top-left points outward on each.
     */
    public function mirrored(): static
    {
        return $this->with(style: $this->style->mirrored());
    }

    /**
     * The quiet zone, in modules.
     */
    public function margin(int $margin): static
    {
        return $this->with(style: $this->style->withMargin($margin));
    }

    public function errorCorrection(ErrorCorrection|string $level): static
    {
        return $this->with(errorCorrection: $level instanceof ErrorCorrection ? $level : (ErrorCorrection::tryFrom(strtoupper($level))
            ?? throw InvalidOptionException::notOneOf(ErrorCorrection::class, 'level', array_column(ErrorCorrection::cases(), 'value'), $level)));
    }

    /**
     * The smallest version to use, so a design keeps one size.
     */
    public function minVersion(int $version): static
    {
        return $this->with(minVersion: $version);
    }

    public function mask(Mask|int $mask): static
    {
        return $this->with(mask: $mask instanceof Mask ? $mask : (Mask::tryFrom($mask)
            ?? throw InvalidOptionException::notOneOf(Mask::class, 'mask', array_map(strval(...), array_column(Mask::cases(), 'value')), (string) $mask)));
    }

    /**
     * Adds a filter after the others: an instance, a provider, a class name or a
     * registered name, with named options - or a closure, which needs `name:` to be cacheable.
     *
     * @param NamedFilter|ProvidesQrFilter|Closure(ModuleContext, ModuleStyle): ModuleStyle|string $filter
     */
    public function filter(NamedFilter|ProvidesQrFilter|Closure|string $filter, mixed ...$options): static
    {
        $name = $filter instanceof Closure ? ($options['name'] ?? null) : null;
        unset($options['name']);

        return $this->with(style: $this->style->withFilter($this->resolve->filter($filter, $options, is_string($name) ? $name : null)));
    }

    /**
     * Replaces the filters with these, each in any form `filter()` takes.
     *
     * @param iterable<NamedFilter|ProvidesQrFilter|string> $filters
     */
    public function filters(iterable $filters): static
    {
        $resolved = [];

        foreach ($filters as $filter) {
            $resolved[] = $this->resolve->filter($filter);
        }

        return $this->with(style: $this->style->withFilters(...$resolved));
    }

    public function withoutFilter(string $name): static
    {
        return $this->with(style: $this->style->withoutFilter($name));
    }

    /**
     * A logo over the code: a file (an uploaded one too), a data: URI, an SVG, raw image bytes, a Logo
     * or something providing one - or a file by path as `file:` - with named options (size, padding,
     * shape, plate, plateRadius, position); or a closure, given the logo's builder.
     *
     * @param Logo|ProvidesQrLogo|SplFileInfo|Closure(LogoBuilder): LogoBuilder|string|null $logo
     */
    public function logo(Logo|ProvidesQrLogo|SplFileInfo|Closure|string|null $logo = null, mixed ...$options): static
    {
        $builder = match (true) {
            $logo instanceof Closure => $logo(new LogoBuilder()),
            $logo === null => new LogoBuilder()->with(...$options),
            default => new LogoBuilder()->from($logo)->with(...$options),
        };

        return $this->with(style: $this->style->withLogo($builder->toLogoStyle()));
    }

    /**
     * Merges styles in, in order, each over what is set so far: a Style or a style
     * builder, a provider, a class name, a registered name - or a closure, given a fresh style builder.
     *
     * @param Style|StyleBuilder|ProvidesQrStyle|Closure(StyleBuilder): StyleBuilder|string ...$styles
     */
    public function style(Style|StyleBuilder|ProvidesQrStyle|Closure|string ...$styles): static
    {
        $style = $this->style;

        foreach ($styles as $other) {
            $style = $style->merge(match (true) {
                $other instanceof Closure => $other(app(Qr::class)->style())->toStyle(),
                $other instanceof StyleBuilder => $other->toStyle(),
                default => $this->resolve->style($other),
            });
        }

        return $this->with(style: $style);
    }

    /**
     * Attribution written into the file, over the config's: title, author, description,
     * copyright, url and keywords as named arguments, or a Metadata.
     *
     * @param list<string> $keywords
     */
    public function metadata(
        ?Metadata $metadata = null,
        ?string $title = null,
        ?string $author = null,
        ?string $description = null,
        ?string $copyright = null,
        ?string $url = null,
        array $keywords = [],
    ): static {
        $given = $metadata ?? new Metadata($title, $author, $description, $copyright, $url, $keywords);

        return $this->with(metadata: $this->metadata->merge($given));
    }

    /**
     * The scan safety guards to apply; ScanSafety::off() for none.
     */
    public function scanSafety(ScanSafety $safety): static
    {
        return $this->with(scanSafety: $safety);
    }

    public function toStyle(): Style
    {
        return $this->style;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->style->toArray();
    }

    /**
     * A copy with the given state changed.
     */
    private function with(
        ?Style $style = null,
        ?ErrorCorrection $errorCorrection = null,
        ?int $minVersion = null,
        ?Mask $mask = null,
        ?Metadata $metadata = null,
        ?ScanSafety $scanSafety = null,
    ): static {
        $changes = compact('style', 'errorCorrection', 'minVersion', 'mask', 'metadata', 'scanSafety');

        return clone($this, array_filter($changes, fn (mixed $value): bool => $value !== null));
    }
}
