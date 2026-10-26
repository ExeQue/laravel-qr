<?php

declare(strict_types=1);

namespace ExeQue\Qr\Builders;

use BackedEnum;
use Closure;
use ExeQue\Qr\Exceptions\ResolveException;
use ExeQue\Qr\Factory;
use ExeQue\Qr\Filters\ClosureFilter;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Filters\Contracts\ProvidesQrFilter;
use ExeQue\Qr\Registries\EyeRegistry;
use ExeQue\Qr\Registries\FilterRegistry;
use ExeQue\Qr\Registries\InnerEyeRegistry;
use ExeQue\Qr\Registries\ModuleRegistry;
use ExeQue\Qr\Registries\OuterEyeRegistry;
use ExeQue\Qr\Registries\StyleRegistry;
use ExeQue\Qr\Rendering\Contracts\ProvidesQrStyle;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrEye;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrInnerEye;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrModule;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrOuterEye;

/**
 * Turns whatever a builder is given into the part it stands for, in this order:
 * an instance, used as it is; an object that provides one, such as an enum case;
 * a class name of either, built through the container with the options; a
 * registered name, looked up with the options.
 *
 * Closures are the builders' own: they hand them a nested builder.
 *
 * @internal
 */
final readonly class Resolve
{
    public function __construct(
        private Factory $factory,
        private ModuleRegistry $modules,
        private OuterEyeRegistry $outerEyes,
        private InnerEyeRegistry $innerEyes,
        private EyeRegistry $eyes,
        private FilterRegistry $filters,
        private StyleRegistry $styles,
    ) {
    }

    /**
     * @param array<array-key, mixed> $options Named options; any other is refused.
     */
    public function module(ModuleShape|ProvidesQrModule|string $module, array $options = []): ModuleShape
    {
        return $this->resolve($module, $options, ModuleShape::class, ProvidesQrModule::class, fn (object $value): ?ModuleShape => $value instanceof ProvidesQrModule ? $value->qrModule() : null, $this->modules->has(...), $this->modules->get(...), 'a module shape');
    }

    /**
     * @param array<array-key, mixed> $options Named options; any other is refused.
     */
    public function outerEye(OuterEyeShape|ProvidesQrOuterEye|string $eye, array $options = []): OuterEyeShape
    {
        return $this->resolve($eye, $options, OuterEyeShape::class, ProvidesQrOuterEye::class, fn (object $value): ?OuterEyeShape => $value instanceof ProvidesQrOuterEye ? $value->qrOuterEye() : null, $this->outerEyes->has(...), $this->outerEyes->get(...), 'an outer eye');
    }

    /**
     * @param array<array-key, mixed> $options Named options; any other is refused.
     */
    public function innerEye(InnerEyeShape|ProvidesQrInnerEye|string $eye, array $options = []): InnerEyeShape
    {
        return $this->resolve($eye, $options, InnerEyeShape::class, ProvidesQrInnerEye::class, fn (object $value): ?InnerEyeShape => $value instanceof ProvidesQrInnerEye ? $value->qrInnerEye() : null, $this->innerEyes->has(...), $this->innerEyes->get(...), 'an inner eye');
    }

    /**
     * A whole eye: an outer and an inner together.
     */
    public function eye(ProvidesQrEye|string $eye): ProvidesQrEye
    {
        return $this->resolve($eye, [], ProvidesQrEye::class, ProvidesQrEye::class, fn (object $value): null => null, $this->eyes->has(...), $this->eyes->get(...), 'a whole eye (outer and inner)');
    }

    /**
     * A filter; a closure becomes a module filter, named `$name` so it can be cached.
     *
     * @param NamedFilter|ProvidesQrFilter|Closure(ModuleContext, ModuleStyle): ModuleStyle|string $filter
     * @param array<array-key, mixed> $options Named options; any other is refused.
     *
     * @return (ModuleFilter&NamedFilter)|(EyeFilter&NamedFilter)
     */
    public function filter(NamedFilter|ProvidesQrFilter|Closure|string $filter, array $options = [], ?string $name = null): NamedFilter
    {
        if ($filter instanceof Closure) {
            return new ClosureFilter($filter, $name);
        }

        $resolved = $this->resolve($filter, $options, NamedFilter::class, ProvidesQrFilter::class, fn (object $value): ?NamedFilter => $value instanceof ProvidesQrFilter ? $value->qrFilter() : null, $this->filters->has(...), $this->filters->get(...), 'a filter');

        return $resolved instanceof ModuleFilter || $resolved instanceof EyeFilter
            ? $resolved
            : throw ResolveException::cannot($filter, 'a module or eye filter');
    }

    public function style(Style|ProvidesQrStyle|string $style): Style
    {
        return $this->resolve($style, [], Style::class, ProvidesQrStyle::class, fn (object $value): ?Style => $value instanceof ProvidesQrStyle ? $value->qrStyle() : null, $this->styles->has(...), $this->styles->get(...), 'a style');
    }

    /**
     * @template T of object
     *
     * @param array<array-key, mixed> $options
     * @param class-string<T> $contract
     * @param class-string $provider What can give a `$contract`; only these and `$contract` are built from a class name.
     * @param Closure(object): (T|null) $provide
     * @param Closure(string): bool $has
     * @param Closure(string, array<string, mixed>): T $get
     *
     * @return T
     */
    private function resolve(mixed $value, array $options, string $contract, string $provider, Closure $provide, Closure $has, Closure $get, string $expected): object
    {
        if ($value instanceof $contract) {
            return $value;
        }

        // An enum case with options is its registered name with them: Module::DotsMedium, diameter: 0.7.
        if ($options !== [] && $value instanceof BackedEnum && $has((string) $value->value)) {
            return $get((string) $value->value, $options);
        }

        if (is_object($value) && ! $value instanceof Closure) {
            return $provide($value) ?? throw ResolveException::cannot($value, $expected);
        }

        if (is_string($value) && (is_a($value, $contract, true) || is_a($value, $provider, true))) {
            return $this->resolve($this->factory->make($value, $options), [], $contract, $provider, $provide, $has, $get, $expected);
        }

        if (is_string($value) && $has($value)) {
            return $get($value, $options);
        }

        throw ResolveException::cannot($value, $expected);
    }
}
