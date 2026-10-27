<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering;

use ExeQue\Qr\Exceptions\InvalidStyleException;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Logos\Enums\LogoShape;
use ExeQue\Qr\Logos\Logo;
use ExeQue\Qr\Logos\LogoStyle;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;
use ExeQue\Qr\Support\Hydrate;
use ExeQue\Qr\Support\Normalizer;

/**
 * A style as plain values and back: what styles are cached, compared and stored
 * by. Settings at their defaults are left out.
 *
 * @internal
 */
final readonly class StyleSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function dehydrate(Style $style): array
    {
        $default = Style::default();

        $eyes = [];

        foreach (Corner::cases() as $corner) {
            $eye = $style->eye($corner);
            $eyes[$corner->value] = array_filter(['outer' => $this->part($eye->outer), 'inner' => $this->part($eye->inner)]);
        }

        $eyes = array_filter($eyes);

        return array_filter([
            'modules' => $this->modules($style->modules),
            'eyes' => $eyes,
            'background' => $style->background === null || Normalizer::value($style->background) === Normalizer::value($default->background) ? null : Normalizer::value($style->background),
            'transparent' => $style->background === null ? true : null,
            'margin' => $style->margin === $default->margin ? null : $style->margin,
            'filters' => array_map(fn (NamedFilter $filter): array => array_filter(['name' => $filter->name(), 'options' => $filter->options()]), $style->filters()),
            'logo' => $style->logo === null ? null : $this->logo($style->logo),
        ], fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * @param array<string, mixed> $data As `dehydrate()` gives it.
     */
    public function hydrate(array $data): Style
    {
        $style = Style::default();
        $modules = (array) ($data['modules'] ?? []);

        $style = $style->withModules($this->moduleStyle($modules, $style->modules));

        foreach ((array) ($data['eyes'] ?? []) as $corner => $eye) {
            $current = $style->eye(Corner::from((string) $corner));
            $style = $style->withEye(Corner::from((string) $corner), new EyeStyle(
                $this->partStyle((array) ($eye['outer'] ?? []), $current->outer, OuterEyeShape::class),
                $this->partStyle((array) ($eye['inner'] ?? []), $current->inner, InnerEyeShape::class),
            ));
        }

        if (($data['transparent'] ?? false) === true) {
            $style = $style->withBackground(null);
        } elseif (isset($data['background'])) {
            $style = $style->withBackground(Hydrate::paint($data['background']));
        }

        if (isset($data['margin'])) {
            $style = $style->withMargin((int) $data['margin']);
        }

        $filters = array_map(fn (mixed $filter): NamedFilter => Hydrate::filter((array) $filter), (array) ($data['filters'] ?? []));

        return $this->withLogo($style->withFilters(...$filters), $data['logo'] ?? null);
    }

    public function merge(Style $base, Style $over): Style
    {
        [$under, $top] = [$this->dehydrate($base), $this->dehydrate($over)];
        unset($under['logo'], $top['logo']);

        // A background, or none, replaces the other.
        if (isset($top['background']) || isset($top['transparent'])) {
            unset($under['background'], $under['transparent']);
        }

        $merged = [
            ...$under,
            ...$top,
            'modules' => [...(array) ($under['modules'] ?? []), ...(array) ($top['modules'] ?? [])],
            'eyes' => $this->mergeEyes((array) ($under['eyes'] ?? []), (array) ($top['eyes'] ?? [])),
            'filters' => [...(array) ($under['filters'] ?? []), ...(array) ($top['filters'] ?? [])],
        ];

        return $this->hydrate($merged)->withLogo($over->logo ?? $base->logo);
    }

    /**
     * @param array<string, mixed> $under
     * @param array<string, mixed> $top
     *
     * @return array<string, mixed>
     */
    private function mergeEyes(array $under, array $top): array
    {
        foreach ($top as $corner => $parts) {
            foreach ((array) $parts as $part => $settings) {
                $under[$corner][$part] = [...(array) ($under[$corner][$part] ?? []), ...(array) $settings];
            }
        }

        return $under;
    }

    /**
     * @return array<string, mixed>
     */
    private function modules(ModuleStyle $module): array
    {
        return $this->changed([
            'shape' => Normalizer::value($module->shape),
            'paint' => Normalizer::value($module->paint),
            'rotation' => $module->rotation,
            'scaleX' => $module->scaleX,
            'scaleY' => $module->scaleY,
            'offsetX' => $module->offsetX,
            'offsetY' => $module->offsetY,
            'skewX' => $module->skewX,
            'skewY' => $module->skewY,
            'opacity' => $module->opacity,
            'skip' => $module->skip,
        ], [
            'shape' => Normalizer::value(Style::default()->modules->shape),
            'paint' => Normalizer::value(Style::default()->modules->paint),
            'rotation' => 0.0, 'scaleX' => 1.0, 'scaleY' => 1.0, 'offsetX' => 0.0, 'offsetY' => 0.0,
            'skewX' => 0.0, 'skewY' => 0.0, 'opacity' => 1.0, 'skip' => false,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function part(EyePartStyle $part): array
    {
        $default = $part->shape instanceof OuterEyeShape ? Style::default()->eye(Corner::TopLeft)->outer : Style::default()->eye(Corner::TopLeft)->inner;

        return $this->changed([
            'shape' => Normalizer::value($part->shape),
            'paint' => $part->paint === null ? null : Normalizer::value($part->paint),
            'rotation' => $part->rotation,
            'scaleX' => $part->scaleX,
            'scaleY' => $part->scaleY,
            'opacity' => $part->opacity,
        ], [
            'shape' => Normalizer::value($default->shape),
            'paint' => null, 'rotation' => 0.0, 'scaleX' => 1.0, 'scaleY' => 1.0, 'opacity' => 1.0,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function moduleStyle(array $data, ModuleStyle $module): ModuleStyle
    {
        return new ModuleStyle(
            isset($data['shape']) ? Hydrate::shape($data['shape'], ModuleShape::class) : $module->shape,
            isset($data['paint']) ? Hydrate::paint($data['paint']) : $module->paint,
            (float) ($data['rotation'] ?? $module->rotation),
            (float) ($data['scaleX'] ?? $module->scaleX),
            (float) ($data['scaleY'] ?? $module->scaleY),
            (float) ($data['offsetX'] ?? $module->offsetX),
            (float) ($data['offsetY'] ?? $module->offsetY),
            (float) ($data['skewX'] ?? $module->skewX),
            (float) ($data['skewY'] ?? $module->skewY),
            (float) ($data['opacity'] ?? $module->opacity),
            (bool) ($data['skip'] ?? $module->skip),
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param class-string<OuterEyeShape|InnerEyeShape> $contract
     */
    private function partStyle(array $data, EyePartStyle $part, string $contract): EyePartStyle
    {
        return new EyePartStyle(
            isset($data['shape']) ? Hydrate::shape($data['shape'], $contract) : $part->shape,
            isset($data['paint']) ? Hydrate::paint($data['paint']) : $part->paint,
            (float) ($data['rotation'] ?? $part->rotation),
            (float) ($data['scaleX'] ?? $part->scaleX),
            (float) ($data['scaleY'] ?? $part->scaleY),
            (float) ($data['opacity'] ?? $part->opacity),
        );
    }

    /**
     * A logo by its hash and settings; where it comes from is the builder's to add as `source`.
     *
     * @return array<string, mixed>
     */
    private function logo(LogoStyle $logo): array
    {
        return array_filter([
            'hash' => $logo->logo->hash,
            'size' => $logo->size ?? 'auto',
            'padding' => $logo->padding,
            'shape' => $logo->shape->value,
            'plate' => $logo->plate === null ? null : Normalizer::value($logo->plate),
            'plateRadius' => $logo->plateRadius,
            'position' => $logo->position,
        ], fn (mixed $value): bool => $value !== null);
    }

    private function withLogo(Style $style, mixed $data): Style
    {
        if (! is_array($data)) {
            return $style;
        }

        // A file by its path, or the image itself: a data: URI, an SVG or image bytes.
        $source = $data['source'] ?? null;
        $logo = match (true) {
            isset($data['file']) => Logo::fromFile((string) $data['file']),
            $source instanceof Logo => $source,
            is_string($source) && str_starts_with($source, 'data:') => Logo::fromDataUri($source),
            is_string($source) => Logo::fromBytes($source),
            default => throw InvalidStyleException::logoWithoutSource(),
        };

        return $style->withLogo(new LogoStyle(
            $logo,
            ($data['size'] ?? 0.2) === 'auto' ? null : (float) ($data['size'] ?? 0.2),
            (int) ($data['padding'] ?? 1),
            LogoShape::from((string) ($data['shape'] ?? LogoShape::Square->value)),
            isset($data['plate']) ? Hydrate::paint($data['plate']) : null,
            (float) ($data['plateRadius'] ?? 0),
            isset($data['position']) ? [(float) $data['position'][0], (float) $data['position'][1]] : null,
        ));
    }

    /**
     * The values that differ from their defaults.
     *
     * @param array<string, mixed> $values
     * @param array<string, mixed> $defaults
     *
     * @return array<string, mixed>
     */
    private function changed(array $values, array $defaults): array
    {
        return array_filter($values, fn (mixed $value, string $key): bool => $value !== $defaults[$key], ARRAY_FILTER_USE_BOTH);
    }
}
