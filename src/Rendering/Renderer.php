<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering;

use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Encoder\Matrix;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\RoundedRect;
use ExeQue\Qr\Geometry\Transform;
use ExeQue\Qr\Logos\Enums\LogoShape;
use ExeQue\Qr\Logos\Knockout;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\Enums\Part;
use ExeQue\Qr\Shapes\Contracts\Directional;

/**
 * Places a code's eyes and modules on a canvas, styled.
 */
final readonly class Renderer
{
    /**
     * Filters run first, in order; the scan safety guards then hold what they did within limits.
     */
    public function render(Matrix $matrix, Style $style, ScanSafety $safety = new ScanSafety()): Scene
    {
        $guard = new Guard($safety, $matrix->errorCorrection, self::dataCells($matrix));
        $style = $style->withMargin($guard->margin($style->margin));
        $knockout = $style->logo === null ? null : Knockout::for($matrix, $style->logo, $safety->maxLogoShare ?? 0.6);

        if ($knockout !== null) {
            $guard->logo($knockout, $matrix);
        }

        $items = [...$this->eyes($matrix, $style, $guard), ...$this->modules($matrix, $style, $guard, $knockout)];

        $guard->contrast($items, $style->background);

        return new Scene(
            $matrix->size + 2 * $style->margin,
            $style->margin,
            $style->background,
            $items,
            $guard->adjustments(),
            $knockout === null ? null : $this->logoLayer($knockout, $style->margin),
        );
    }

    /**
     * @return list<SceneItem>
     */
    private function eyes(Matrix $matrix, Style $style, Guard $guard): array
    {
        $items = [];

        foreach (Corner::cases() as $corner) {
            $context = EyeContext::of($corner, $matrix->size);
            $eye = $style->eye($corner);

            foreach ($style->filters() as $filter) {
                if ($filter instanceof EyeFilter) {
                    $eye = $filter->eye($context, $eye);
                }
            }

            $eye = $guard->eye($context, $eye);
            [$x, $y] = $corner->center($matrix->size);
            $center = [$x + $style->margin, $y + $style->margin];

            $items[] = $this->eyePart(Part::OuterEye, $corner, $eye->outer, $center, $style);
            $items[] = $this->eyePart(Part::InnerEye, $corner, $eye->inner, $center, $style);
        }

        return $items;
    }

    /**
     * @param array{float, float} $center
     */
    private function eyePart(Part $part, Corner $corner, EyePartStyle $eye, array $center, Style $style): SceneItem
    {
        return new SceneItem(
            part: $part,
            role: Role::Finder,
            corner: $corner,
            cell: null,
            shapeName: self::shapeName($eye->shape),
            // A part with one distinct corner is turned so that corner points out on every corner.
            shape: $eye->shape instanceof Directional && $eye->shape->isDirectional()
                ? $eye->shape->path()->transform(Transform::identity()->rotate($corner->turn()))
                : $eye->shape->path(),
            transform: $eye->transform()->translate(...$center),
            center: $center,
            paint: $eye->paint ?? $style->modules->paint,
            opacity: $eye->opacity,
            inheritsPaint: $eye->paint === null,
        );
    }

    /**
     * @return list<SceneItem>
     */
    private function modules(Matrix $matrix, Style $style, Guard $guard, ?Knockout $knockout): array
    {
        $items = [];

        for ($row = 0; $row < $matrix->size; $row++) {
            for ($column = 0; $column < $matrix->size; $column++) {
                // The logo's area stays clear, whatever the filters do.
                if (! $matrix->isDark($column, $row) || $matrix->role($column, $row) === Role::Finder || $knockout?->clears($column, $row)) {
                    continue;
                }

                $cell = ModuleContext::of($matrix, $column, $row, $knockout?->area, $knockout);
                $module = $style->modules;

                foreach ($style->filters() as $filter) {
                    if ($filter instanceof ModuleFilter) {
                        $module = $filter->module($cell, $module);
                    }
                }

                $module = $guard->module($cell, $module);

                if ($module->skip) {
                    continue;
                }

                $center = [$cell->x + $style->margin, $cell->y + $style->margin];

                $items[] = new SceneItem(
                    part: Part::Module,
                    role: $cell->role,
                    corner: null,
                    cell: [$column, $row],
                    shapeName: self::shapeName($module->shape),
                    shape: $module->shape->draw($cell),
                    transform: $module->transform()->translate(...$center),
                    center: $center,
                    paint: $module->paint,
                    opacity: $module->opacity,
                    inheritsPaint: $module->paint === $style->modules->paint,
                );
            }
        }

        return $items;
    }

    /**
     * The logo on the canvas: its box, the area its plate fills and the outline it is cropped to.
     */
    private function logoLayer(Knockout $knockout, int $margin): LogoLayer
    {
        $style = $knockout->style;
        [$x, $y, $width, $height] = $knockout->box;
        $padding = $style->padding;
        $toCanvas = Transform::identity()->translate($margin, $margin);

        [$area, $crop] = match ($style->shape) {
            LogoShape::Circle => [
                RoundedRect::circle($x + $width / 2, $y + $height / 2, max($width, $height) / 2 + $padding),
                RoundedRect::circle($x + $width / 2, $y + $height / 2, max($width, $height) / 2),
            ],
            LogoShape::Rounded => [
                RoundedRect::path($x - $padding, $y - $padding, $width + 2 * $padding, $height + 2 * $padding, min($width, $height) / 4 + $padding),
                RoundedRect::path($x, $y, $width, $height, min($width, $height) / 4),
            ],
            LogoShape::Alpha => [Path::merge(array_map(
                fn (array $cell): Path => RoundedRect::path($cell[0], $cell[1], 1, 1),
                $knockout->clearedCells(),
            )), null],
            LogoShape::Square => [
                RoundedRect::path($x - $padding, $y - $padding, $width + 2 * $padding, $height + 2 * $padding, $style->plateRadius),
                null,
            ],
        };

        return new LogoLayer(
            $style,
            [$x + $margin, $y + $margin, $width, $height],
            $area->transform($toCanvas),
            $crop?->transform($toCanvas),
        );
    }

    /**
     * Every data cell, dark or light.
     */
    private static function dataCells(Matrix $matrix): int
    {
        $count = 0;

        for ($row = 0; $row < $matrix->size; $row++) {
            for ($column = 0; $column < $matrix->size; $column++) {
                $count += $matrix->role($column, $row) === Role::Data ? 1 : 0;
            }
        }

        return $count;
    }

    private static function shapeName(object $shape): string
    {
        return substr($shape::class, strrpos($shape::class, '\\') + 1);
    }
}
