<?php

declare(strict_types=1);

namespace ExeQue\Qr\Validation;

use ExeQue\Qr\Exceptions\InvalidStyleException;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Logos\Enums\LogoShape;
use ExeQue\Qr\Logos\Logo;
use ExeQue\Qr\Logos\LogoStyle;
use ExeQue\Qr\Registries\EyeRegistry;
use ExeQue\Qr\Registries\FilterRegistry;
use ExeQue\Qr\Registries\InnerEyeRegistry;
use ExeQue\Qr\Registries\ModuleRegistry;
use ExeQue\Qr\Registries\OuterEyeRegistry;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\EyePartStyle;
use ExeQue\Qr\Rendering\EyeStyle;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Support\Hydrate;
use ExeQue\Qr\Support\Normalizer;
use Throwable;

/**
 * Turns validated style input into a style, and a style back into input to fill a form with.
 *
 * @internal
 */
final class StyleInput
{
    /**
     * The input applied over a style: what it leaves out stays as the style has it.
     *
     * @param array<array-key, mixed> $input
     */
    public static function apply(array $input, Style $style, ?Logo $logo = null): Style
    {
        if (isset($input['modules'])) {
            $style = $style->withModules(self::modules((array) $input['modules'], $style->modules));
        }

        foreach (Corner::cases() as $corner) {
            $eyes = (array) ($input['eyes'] ?? []);
            $eye = self::eye($eyes, $style->eye($corner));
            $style = $style->withEye($corner, self::eye((array) ($eyes['corners'][$corner->value] ?? []), $eye));
        }

        if (array_key_exists('background', $input)) {
            $style = $style->withBackground($input['background'] === null ? null : Hydrate::paint($input['background']));
        }

        if (isset($input['margin'])) {
            $style = $style->withMargin((int) $input['margin']);
        }

        if (isset($input['filters'])) {
            $style = $style->withFilters(...array_map(fn (mixed $filter): NamedFilter => Hydrate::filter((array) $filter), (array) $input['filters']));
        }

        if (isset($input['logo'])) {
            $style = $style->withLogo(self::logo((array) $input['logo'], $logo ?? ($style->logo !== null ? $style->logo->logo : null) ?? throw InvalidStyleException::logoWithoutSource(), $style->logo));
        }

        return $style;
    }

    /**
     * The style as input, settings at their defaults left out.
     *
     * @return array<string, mixed>
     */
    public static function of(Style $style): array
    {
        $default = Style::default();

        return array_filter([
            'modules' => self::modulesOf($style->modules, $default->modules),
            'eyes' => self::eyesOf($style),
            'background' => Normalizer::value($style->background) === Normalizer::value($default->background) ? false : Normalizer::value($style->background),
            'margin' => $style->margin === $default->margin ? false : $style->margin,
            'filters' => array_map(fn (NamedFilter $filter): array => array_filter([
                'name' => app(FilterRegistry::class)->has($filter->name()) ? $filter->name() : throw InvalidStyleException::notInput("filter {$filter->name()}"),
                'options' => self::named($filter->options()),
            ]), $style->filters()) ?: false,
            'logo' => $style->logo === null ? false : array_filter([
                'size' => $style->logo->size ?? 'auto',
                'padding' => $style->logo->padding,
                'shape' => $style->logo->shape->value,
                'plate' => $style->logo->plate === null ? null : Normalizer::value($style->logo->plate),
                'plateRadius' => $style->logo->plateRadius,
                'position' => $style->logo->position,
            ], fn (mixed $value): bool => $value !== null),
        ], fn (mixed $value): bool => $value !== false && $value !== []);
    }

    /**
     * @param array<array-key, mixed> $input
     */
    private static function modules(array $input, ModuleStyle $modules): ModuleStyle
    {
        if (isset($input['shape'])) {
            $modules = $modules->withShape(app(ModuleRegistry::class)->get((string) $input['shape'], (array) ($input['options'] ?? [])));
        }

        if (isset($input['paint'])) {
            $modules = $modules->withPaint(Hydrate::paint($input['paint']));
        }

        if (isset($input['scaleX']) || isset($input['scaleY'])) {
            $modules = $modules->withSize((float) ($input['scaleX'] ?? $modules->scaleX), (float) ($input['scaleY'] ?? $modules->scaleY));
        }

        if (isset($input['rotation'])) {
            $modules = $modules->withRotate((float) $input['rotation']);
        }

        return isset($input['opacity']) ? $modules->withOpacity((float) $input['opacity']) : $modules;
    }

    /**
     * One eye's input, shared or a corner's, over the eye as it is.
     *
     * @param array<array-key, mixed> $input
     */
    private static function eye(array $input, EyeStyle $eye): EyeStyle
    {
        if (isset($input['preset'])) {
            $preset = app(EyeRegistry::class)->get((string) $input['preset']);
            $eye = $eye->withOuter($eye->outer->withShape($preset->qrOuterEye()))->withInner($eye->inner->withShape($preset->qrInnerEye()));
        }

        if (isset($input['paint'])) {
            $eye = $eye->withPaint(Hydrate::paint($input['paint']));
        }

        return $eye
            ->withOuter(self::part((array) ($input['outer'] ?? []), $eye->outer, 'outer'))
            ->withInner(self::part((array) ($input['inner'] ?? []), $eye->inner, 'inner'));
    }

    /**
     * @param array<array-key, mixed> $input
     */
    private static function part(array $input, EyePartStyle $part, string $kind): EyePartStyle
    {
        if (isset($input['shape'])) {
            $registry = $kind === 'outer' ? app(OuterEyeRegistry::class) : app(InnerEyeRegistry::class);
            $part = $part->withShape($registry->get((string) $input['shape'], (array) ($input['options'] ?? [])));
        }

        if (isset($input['paint'])) {
            $part = $part->withPaint(Hydrate::paint($input['paint']));
        }

        if (isset($input['scaleX']) || isset($input['scaleY'])) {
            $part = $part->withSize((float) ($input['scaleX'] ?? $part->scaleX), (float) ($input['scaleY'] ?? $part->scaleY));
        }

        if (isset($input['rotation'])) {
            $part = $part->withRotate((float) $input['rotation']);
        }

        return isset($input['opacity']) ? $part->withOpacity((float) $input['opacity']) : $part;
    }

    /**
     * @param array<array-key, mixed> $input
     */
    private static function logo(array $input, Logo $logo, ?LogoStyle $current): LogoStyle
    {
        $style = $current === null ? new LogoStyle($logo) : new LogoStyle($logo, $current->size, $current->padding, $current->shape, $current->plate, $current->plateRadius, $current->position);

        if (array_key_exists('size', $input)) {
            $style = $style->withSize($input['size'] === 'auto' ? null : (float) $input['size']);
        }

        if (array_key_exists('plate', $input) || isset($input['plateRadius'])) {
            $plate = array_key_exists('plate', $input) ? ($input['plate'] === null ? null : Hydrate::paint($input['plate'])) : $style->plate;
            $style = $style->withPlate($plate, (float) ($input['plateRadius'] ?? $style->plateRadius));
        }

        if (array_key_exists('position', $input)) {
            $position = $input['position'];
            $style = $style->withPosition(is_array($position) ? [(float) $position[0], (float) $position[1]] : null);
        }

        if (isset($input['padding'])) {
            $style = $style->withPadding((int) $input['padding']);
        }

        return isset($input['shape']) ? $style->withShape(LogoShape::from((string) $input['shape'])) : $style;
    }

    /**
     * @return array<string, mixed>
     */
    private static function modulesOf(ModuleStyle $modules, ModuleStyle $default): array
    {
        return array_filter([
            ...($modules->shape == $default->shape ? [] : self::shapeOf($modules->shape, app(ModuleRegistry::class), 'module shape')),
            'paint' => Normalizer::value($modules->paint) === Normalizer::value($default->paint) ? null : Normalizer::value($modules->paint),
            'rotation' => $modules->rotation === 0.0 ? null : $modules->rotation,
            'scaleX' => $modules->scaleX === 1.0 ? null : $modules->scaleX,
            'scaleY' => $modules->scaleY === 1.0 ? null : $modules->scaleY,
            'opacity' => $modules->opacity === 1.0 ? null : $modules->opacity,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * The eyes: shared when every corner is alike, by corner when not; a preset where a corner's
     * parts are one's and nothing else is set on them.
     *
     * @return array<string, mixed>
     */
    private static function eyesOf(Style $style): array
    {
        $default = self::cornerOf(Style::default()->eye(Corner::TopLeft));
        $corners = [];

        foreach (Corner::cases() as $corner) {
            $entry = self::cornerOf($style->eye($corner));
            $corners[$corner->value] = $entry === $default ? [] : $entry;
        }

        $shared = array_unique(array_map(serialize(...), $corners));

        return count($shared) === 1 ? $corners[Corner::TopLeft->value] : ['corners' => array_filter($corners)];
    }

    /**
     * One corner's eye as input: a paint for both parts where they share one, and a preset where
     * the parts are one's and nothing else is set on them.
     *
     * @return array<string, mixed>
     */
    private static function cornerOf(EyeStyle $eye): array
    {
        $outer = self::partOf($eye->outer, app(OuterEyeRegistry::class), 'outer eye');
        $inner = self::partOf($eye->inner, app(InnerEyeRegistry::class), 'inner eye');
        $paint = isset($outer['paint'], $inner['paint']) && $outer['paint'] === $inner['paint'] ? $outer['paint'] : null;

        if ($paint !== null) {
            unset($outer['paint'], $inner['paint']);
        }

        $preset = array_keys($outer) === ['shape'] && array_keys($inner) === ['shape'] ? self::presetOf($eye) : null;

        return array_filter([
            ...($preset !== null ? ['preset' => $preset] : ['outer' => $outer, 'inner' => $inner]),
            'paint' => $paint,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private static function partOf(EyePartStyle $part, OuterEyeRegistry|InnerEyeRegistry $registry, string $what): array
    {
        return array_filter([
            ...self::shapeOf($part->shape, $registry, $what),
            'paint' => $part->paint === null ? null : Normalizer::value($part->paint),
            'rotation' => $part->rotation === 0.0 ? null : $part->rotation,
            'scaleX' => $part->scaleX === 1.0 ? null : $part->scaleX,
            'scaleY' => $part->scaleY === 1.0 ? null : $part->scaleY,
            'opacity' => $part->opacity === 1.0 ? null : $part->opacity,
        ], fn (mixed $value): bool => $value !== null);
    }

    private static function presetOf(EyeStyle $eye): ?string
    {
        $registry = app(EyeRegistry::class);

        foreach ($registry->names() as $name) {
            $preset = $registry->get($name);

            if ($preset->qrOuterEye() == $eye->outer->shape && $preset->qrInnerEye() == $eye->inner->shape) {
                return $name;
            }
        }

        return null;
    }

    /**
     * A shape by its registered name, with options when it differs from what the name gives.
     *
     * @return array{shape: string, options?: array<string, mixed>}
     */
    private static function shapeOf(object $shape, ModuleRegistry|OuterEyeRegistry|InnerEyeRegistry $registry, string $what): array
    {
        foreach ($registry->names() as $name) {
            if (self::tryGet($registry, $name) == $shape) {
                return ['shape' => $name];
            }
        }

        $name = $registry->nameOf($shape::class) ?? throw InvalidStyleException::notInput($what);

        return ['shape' => $name, 'options' => $shape instanceof Configurable ? (array) Normalizer::value($shape->options()) : []];
    }

    private static function tryGet(ModuleRegistry|OuterEyeRegistry|InnerEyeRegistry $registry, string $name): ?object
    {
        try {
            return $registry->get($name);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Filter options with any shape in them by its registered name, as input gives shapes.
     */
    private static function named(mixed $value): mixed
    {
        if (is_array($value) && array_keys($value) === ['class', 'options'] && is_string($value['class'])) {
            foreach ([app(ModuleRegistry::class), app(OuterEyeRegistry::class), app(InnerEyeRegistry::class)] as $registry) {
                foreach ($registry->names() as $name) {
                    $shape = self::tryGet($registry, $name);

                    if ($shape instanceof Configurable && $shape::class === $value['class'] && Normalizer::value($shape->options()) == $value['options']) {
                        return $name;
                    }
                }
            }

            return $value;
        }

        if (is_string($value) && class_exists($value)) {
            foreach ([app(ModuleRegistry::class), app(OuterEyeRegistry::class), app(InnerEyeRegistry::class)] as $registry) {
                $name = $registry->nameOf($value);

                if ($name !== null) {
                    return $name;
                }
            }
        }

        return is_array($value) ? array_map(self::named(...), $value) : $value;
    }
}
