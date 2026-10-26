<?php

declare(strict_types=1);

namespace ExeQue\Qr\Icons;

use ExeQue\Qr\Builders\Resolve;
use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\Transform;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrEye;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrInnerEye;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrModule;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrOuterEye;

/**
 * Draws shapes on their own, for style pickers and settings screens, from the same paths
 * the codes are drawn with.
 */
final readonly class Icons
{
    /**
     * The cells of a module icon, so shapes that join their neighbors show how.
     */
    private const array MODULES = [[0, 0], [2, 0], [1, 1], [0, 2], [1, 2]];

    private const int PRECISION = 2;

    public function __construct(
        private Resolve $resolve,
    ) {
    }

    /**
     * Five modules in a 3x3 box: `viewBox="0 0 3 3"`.
     */
    public function module(ModuleShape|ProvidesQrModule|string $shape): Icon
    {
        $shape = $this->resolve->module($shape);
        $paths = [];

        foreach (self::MODULES as [$column, $row]) {
            $cell = new ModuleContext($column, $row, 3, Role::Data, self::neighbors($column, $row), crc32("{$column},{$row}"));
            $paths[] = $shape->draw($cell)->transform(Transform::identity()->translate($cell->x, $cell->y));
        }

        return self::svg(3, [Path::merge($paths)]);
    }

    /**
     * The ring alone, in an 8x8 box with half a module of air around it.
     */
    public function outerEye(OuterEyeShape|ProvidesQrOuterEye|string $shape, ?Corner $corner = null): Icon
    {
        return self::svg(8, [self::place($this->resolve->outerEye($shape)->path(), $corner)]);
    }

    /**
     * The middle of an eye, with the square outer eye faintly behind it so its size reads right.
     */
    public function innerEye(InnerEyeShape|ProvidesQrInnerEye|string $shape, bool $withOuter = true, ?Corner $corner = null): Icon
    {
        $inner = self::place($this->resolve->innerEye($shape)->path(), $corner);

        if (! $withOuter) {
            return self::svg(8, [$inner]);
        }

        return self::svg(8, [$inner], self::place($this->resolve->outerEye('square')->path(), $corner));
    }

    /**
     * A whole eye: an outer and an inner together, given as a preset or apart.
     */
    public function eye(
        ProvidesQrEye|string|null $eye = null,
        OuterEyeShape|ProvidesQrOuterEye|string|null $outer = null,
        InnerEyeShape|ProvidesQrInnerEye|string|null $inner = null,
        ?Corner $corner = null,
    ): Icon {
        $preset = $eye === null ? null : $this->resolve->eye($eye);
        $outer = $outer === null ? $preset?->qrOuterEye() ?? 'square' : $outer;
        $inner = $inner === null ? $preset?->qrInnerEye() ?? 'square' : $inner;

        return self::svg(8, [
            self::place($this->resolve->outerEye($outer)->path(), $corner),
            self::place($this->resolve->innerEye($inner)->path(), $corner),
        ]);
    }

    /**
     * @return array<string, bool>
     */
    private static function neighbors(int $column, int $row): array
    {
        $neighbors = [];

        foreach ([-1, 0, 1] as $dy) {
            foreach ([-1, 0, 1] as $dx) {
                $neighbors["{$dx},{$dy}"] = in_array([$column + $dx, $row + $dy], self::MODULES, true);
            }
        }

        return $neighbors;
    }

    /**
     * An eye part moved to the middle of the 8x8 box, turned as it sits on `$corner` in a mirrored style.
     */
    private static function place(Path $path, ?Corner $corner): Path
    {
        $degrees = match ($corner) {
            Corner::TopRight => 90.0,
            Corner::BottomLeft => -90.0,
            default => 0.0,
        };

        return $path->transform(Transform::identity()->rotate($degrees)->translate(4, 4));
    }

    /**
     * @param list<Path> $paths
     */
    private static function svg(int $box, array $paths, ?Path $faint = null): Icon
    {
        // Some shapes reach a little past their box, as a regular hexagon ring does.
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$box.' '.$box.'" fill="currentColor" overflow="visible">';

        if ($faint !== null) {
            $svg .= '<path opacity="0.3" d="'.$faint->toCompactSvg(self::PRECISION).'"/>';
        }

        foreach ($paths as $path) {
            $svg .= '<path d="'.$path->toCompactSvg(self::PRECISION).'"/>';
        }

        return new Icon($svg.'</svg>');
    }
}
