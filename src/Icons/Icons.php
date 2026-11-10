<?php

declare(strict_types=1);

namespace ExeQue\Qr\Icons;

use BackedEnum;
use Closure;
use Composer\InstalledVersions;
use ExeQue\Qr\Builders\Pipeline;
use ExeQue\Qr\Builders\Resolve;
use ExeQue\Qr\Colors\Hex;
use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Filters\Contracts\ProvidesQrFilter;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\Transform;
use ExeQue\Qr\Gradients\Contracts\Gradient;
use ExeQue\Qr\Gradients\LinearGradient;
use ExeQue\Qr\Paints\Layered;
use ExeQue\Qr\Registries\EyeRegistry;
use ExeQue\Qr\Registries\FilterRegistry;
use ExeQue\Qr\Registries\InnerEyeRegistry;
use ExeQue\Qr\Registries\ModuleRegistry;
use ExeQue\Qr\Registries\OuterEyeRegistry;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\EyePartStyle;
use ExeQue\Qr\Rendering\EyeStyle;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrEye;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrInnerEye;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrModule;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrOuterEye;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * Draws shapes on their own, for style pickers and settings screens, from the same paths
 * the codes are drawn with.
 *
 * Icons of the package's own shapes and filters, given by name or by the package's enums, are
 * drawn once per request, and kept in a cache store when the icon cache is enabled (qr.cache.icons);
 * qr:clear and package updates make them stale. Objects and names the application registered
 * are drawn every time, as they can change.
 */
final class Icons
{
    /**
     * The cells of a module icon, so shapes that join their neighbors show how.
     */
    private const array MODULES = [[0, 0], [2, 0], [1, 1], [0, 2], [1, 2]];

    private const int PRECISION = 2;

    /**
     * The side of a filter icon's sample of modules, all dark and apart, so each one's change shows.
     */
    private const int SAMPLE = 5;

    /**
     * The built-in filters drawn stronger than their defaults, so what they do shows at icon size;
     * for those that need options, options to show them with.
     *
     * @var array<string, array<string, mixed>>
     */
    public const array FILTER_OPTIONS = [
        'mixed-rotation' => ['min' => -35, 'max' => 35],
        'mixed-size' => ['min' => 0.4, 'max' => 1.2],
        'mixed-offset' => ['max' => 0.22],
        'mixed-skew' => ['x' => 25, 'y' => 10],
        'mixed-opacity' => ['min' => 0.2],
        'mixed-shade' => ['min' => -70, 'max' => 70],
        'mixed-shape' => ['shapes' => ['square', 'dots-large', 'diamonds']],
        'palette' => ['colors' => ['#000000', '#777777', '#bbbbbb']],
        'jitter' => ['rotate' => 25, 'size' => 0.3, 'offset' => 0.15],
        'jitter-3d' => ['tilt' => 60],
        'rotation-3d' => ['x' => [-65, 65], 'y' => [-65, 65]],
        'gradient-fill' => ['gradient' => ['kind' => 'linear', 'angle' => 135, 'stops' => [['color' => '#000000', 'position' => 0], ['color' => '#cccccc', 'position' => 1]]]],
        'by-role' => ['roles' => ['data'], 'filter' => ['name' => 'mixed-size', 'options' => ['min' => 0.5, 'max' => 0.5]]],
        'region' => ['region' => ['kind' => 'circle', 'x' => 2.5, 'y' => 2.5, 'radius' => 1.6], 'filter' => ['name' => 'mixed-size', 'options' => ['min' => 0.45, 'max' => 0.45]]],
        'wave' => ['property' => 'size', 'amplitude' => 0.35, 'wavelength' => 4],
        'radial' => ['property' => 'size', 'inner' => 1.1, 'outer' => 0.3],
        'compound' => ['filters' => [['name' => 'mixed-rotation', 'options' => ['min' => -30, 'max' => 30]], ['name' => 'mixed-opacity', 'options' => ['min' => 0.3]]]],
        'eye-tilt' => ['outer' => 12, 'inner' => -25],
        'eye-size' => ['inner' => 0.6],
        'eye-paint' => ['outer' => '#aaaaaa', 'inner' => '#000000'],
        'eye-palette' => ['colors' => ['#aaaaaa'], 'inner' => false],
        'eye-shade' => ['min' => 45, 'max' => 45],
        'eye-jitter' => ['rotate' => 15, 'size' => 0.15, 'opacity' => 0.5],
        'eye-twist' => ['step' => 25],
        'eye-echo' => [],
        'eye-stretch' => ['width' => 1.25, 'height' => 0.8],
        'eye-flip' => ['vertical' => true],
        'eye-jitter-3d' => ['tilt' => 40],
        'eye-rotation-3d' => ['x' => 45, 'y' => 35],
    ];

    /**
     * @var array<string, Icon> Icons drawn in this request, by what they were drawn from.
     */
    private array $drawn = [];

    private ?string $version = null;

    public function __construct(
        private readonly Resolve $resolve,
        private readonly ModuleRegistry $modules,
        private readonly OuterEyeRegistry $outerEyes,
        private readonly InnerEyeRegistry $innerEyes,
        private readonly EyeRegistry $eyes,
        private readonly FilterRegistry $filters,
        private readonly Pipeline $pipeline,
        private readonly CacheFactory $caches,
        private readonly Config $config,
    ) {
    }

    /**
     * Five modules in a 3x3 box: `viewBox="0 0 3 3"`.
     */
    public function module(ModuleShape|ProvidesQrModule|string $shape): Icon
    {
        return $this->remember(['module', $this->name($shape, $this->modules->isBuiltIn(...))], fn (): Icon => $this->drawModule($shape));
    }

    private function drawModule(ModuleShape|ProvidesQrModule|string $shape): Icon
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
        return $this->remember(['outer', $this->name($shape, $this->outerEyes->isBuiltIn(...)), $corner->value ?? '-'], fn (): Icon => self::svg(8, [self::place($this->resolve->outerEye($shape)->path(), $corner)]));
    }

    /**
     * The middle of an eye, with the square outer eye faintly behind it so its size reads right.
     */
    public function innerEye(InnerEyeShape|ProvidesQrInnerEye|string $shape, bool $withOuter = true, ?Corner $corner = null): Icon
    {
        return $this->remember(
            ['inner', $this->name($shape, $this->innerEyes->isBuiltIn(...)), $withOuter ? $this->name('square', $this->outerEyes->isBuiltIn(...)) : '-', $corner->value ?? '-'],
            fn (): Icon => $this->drawInnerEye($shape, $withOuter, $corner),
        );
    }

    private function drawInnerEye(InnerEyeShape|ProvidesQrInnerEye|string $shape, bool $withOuter, ?Corner $corner): Icon
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
        // A preset's own parts are drawn from its name; parts not given and without a preset are squares.
        $part = fn (mixed $given, Closure $isBuiltIn): ?string => match (true) {
            $given !== null => $this->name($given, $isBuiltIn),
            $eye !== null => '-',
            default => $this->name('square', $isBuiltIn),
        };

        return $this->remember([
            'eye',
            $eye === null ? '-' : $this->name($eye, $this->eyes->isBuiltIn(...)),
            $part($outer, $this->outerEyes->isBuiltIn(...)),
            $part($inner, $this->innerEyes->isBuiltIn(...)),
            $corner->value ?? '-',
        ], function () use ($eye, $outer, $inner, $corner): Icon {
            $preset = $eye === null ? null : $this->resolve->eye($eye);

            return self::svg(8, [
                self::place($this->resolve->outerEye($outer ?? $preset?->qrOuterEye() ?? 'square')->path(), $corner),
                self::place($this->resolve->innerEye($inner ?? $preset?->qrInnerEye() ?? 'square')->path(), $corner),
            ]);
        });
    }

    /**
     * What a filter does: a module filter on a 5x5 sample of square modules (`viewBox="0 0 5 5"`), the
     * top row and left column timing modules,
     * an eye filter on one eye (`viewBox="0 0 8 8"`). The built-in filters, given by name, are drawn
     * stronger than their defaults. Colors show as how dark they are, as the icon is one color.
     */
    public function filter(NamedFilter|ProvidesQrFilter|string $filter): Icon
    {
        return $this->remember([
            'filter',
            $this->name($filter, $this->filters->isBuiltIn(...)),
            // The shapes the sample is drawn with.
            $this->name('square', $this->modules->isBuiltIn(...)),
            $this->name('round-corner', $this->outerEyes->isBuiltIn(...)),
            $this->name('round-corner', $this->innerEyes->isBuiltIn(...)),
        ], fn (): Icon => $this->drawFilter($filter));
    }

    private function drawFilter(NamedFilter|ProvidesQrFilter|string $filter): Icon
    {
        $filter = is_string($filter) && array_key_exists($filter, self::FILTER_OPTIONS)
            ? $this->resolve->filter($filter, self::FILTER_OPTIONS[$filter])
            : $this->resolve->filter($filter);

        if ($filter instanceof ModuleFilter) {
            $modules = $this->filteredModules($filter);

            // A filter that leaves modules alone, as a compound of eye filters does, is drawn on an eye.
            if ($modules['changed'] || ! $filter instanceof EyeFilter) {
                return self::shades(self::SAMPLE, $modules['parts']);
            }
        }

        return $this->filteredEye($filter);
    }

    /**
     * @return array{parts: list<array{Path, float}>, changed: bool}
     */
    private function filteredModules(ModuleFilter $filter): array
    {
        $base = new ModuleStyle($this->resolve->module('square'), new Hex('#000000'))->withSize(0.7);
        $parts = [];
        $changed = false;

        for ($row = 0; $row < self::SAMPLE; $row++) {
            for ($column = 0; $column < self::SAMPLE; $column++) {
                // The top row and left column stand for timing patterns, so filters limited by role show it.
                $role = $column === 0 || $row === 0 ? Role::Timing : Role::Data;
                $cell = new ModuleContext($column, $row, self::SAMPLE, $role, [], crc32("{$column},{$row}"));
                $style = $filter->module($cell, $base);
                $changed = $changed || $style != $base;

                if ($style->skip) {
                    continue;
                }

                $paint = self::darkness($style->paint, ($column + 0.5) / self::SAMPLE, ($row + 0.5) / self::SAMPLE);
                $parts[] = [
                    $style->shape->draw($cell)->transform($style->transform()->translate($cell->x, $cell->y)),
                    $style->opacity * $paint,
                ];
            }
        }

        return ['parts' => $parts, 'changed' => $changed];
    }

    private function filteredEye(EyeFilter $filter): Icon
    {
        // The top-right eye, which filters that step from corner to corner have turned; one round
        // corner, so turns and flips show; a lighter middle, so filters that recolor a part show;
        // modules light toward the right, so filters that take their color show.
        $context = EyeContext::of(Corner::TopRight, 21, LinearGradient::make('#000000', '#cccccc'));
        $eye = EyeStyle::of($this->resolve->outerEye('round-corner'), $this->resolve->innerEye('round-corner'));
        $eye = $eye->withOuter($eye->outer->withPaint(new Hex('#000000')))->withInner($eye->inner->withPaint(new Hex('#777777')));
        $eye = $filter->eye($context, $eye);

        $part = fn (EyePartStyle $part): array => [
            $part->shape->path()->transform($part->transform()->translate(4, 4)),
            $part->opacity * self::darkness($context->paintOf($part) ?? new Hex('#000000'), 0.15, 0.15),
        ];

        return self::shades(8, [$part($eye->outer), $part($eye->inner)]);
    }

    /**
     * The icon drawn from these parts: from this request, then the cache store, then drawn. Drawn
     * every time when a part is null, as it was not the package's own; a failed draw is not kept.
     *
     * @param list<string|null> $parts
     * @param Closure(): Icon $draw
     */
    private function remember(array $parts, Closure $draw): Icon
    {
        if (in_array(null, $parts, true)) {
            return $draw();
        }

        $key = implode('|', $parts);

        if (isset($this->drawn[$key])) {
            return $this->drawn[$key];
        }

        if (! (bool) $this->setting('enabled', false)) {
            return $this->drawn[$key] = $draw();
        }

        $store = $this->caches->store($this->setting('store', null));
        $this->version ??= $this->pipeline->version().'.'.(InstalledVersions::getReference('exeque/laravel-qr') ?? InstalledVersions::getVersion('exeque/laravel-qr') ?? '');
        $storeKey = $this->config->get('qr.cache.prefix', 'qr').':icon:'.$this->version.':'.hash('xxh128', $key);
        $svg = $store->get($storeKey);

        if (is_string($svg)) {
            return $this->drawn[$key] = new Icon($svg);
        }

        $icon = $this->drawn[$key] = $draw();
        $store->put($storeKey, $icon->svg, (int) $this->setting('ttl', 604800));

        return $icon;
    }

    /**
     * An icon cache setting, or the code cache's where a published config has no icons block.
     */
    private function setting(string $key, mixed $default): mixed
    {
        return $this->config->get('qr.cache.icons.'.$key, $this->config->get('qr.cache.'.$key, $default));
    }

    /**
     * What a shape or filter is called, when it is the package's own: a built-in name, or one of the
     * package's enum cases, which are told apart from names as filter cases draw with their defaults.
     *
     * @param Closure(string): bool $isBuiltIn
     */
    private function name(object|string $value, Closure $isBuiltIn): ?string
    {
        return match (true) {
            is_string($value) => $isBuiltIn($value) ? $value : null,
            $value instanceof BackedEnum && str_starts_with($value::class, 'ExeQue\\Qr\\') => $value::class.'::'.$value->value,
            default => null,
        };
    }

    /**
     * How dark a paint is, 0-1, where it is read: black is whole, white faint.
     */
    private static function darkness(Paint $paint, float $x, float $y): float
    {
        $color = match (true) {
            $paint instanceof Layered => $paint->layers[0]->paint,
            default => $paint,
        };
        $color = $color instanceof Gradient ? $color->colorAt($x, $y) : $color;

        if (! $color instanceof Color) {
            return 1.0;
        }

        $rgba = $color->toRgba();
        $lightness = (0.2126 * $rgba->red + 0.7152 * $rgba->green + 0.0722 * $rgba->blue) / 255;

        return round($rgba->alpha * (1 - 0.8 * $lightness), 2);
    }

    /**
     * Paths in one color, each part as dark as given, alike ones sharing a path.
     *
     * @param list<array{Path, float}> $parts
     */
    private static function shades(int $box, array $parts): Icon
    {
        $groups = [];

        foreach ($parts as [$path, $opacity]) {
            $groups[(string) round(max(0.0, min(1.0, $opacity)), 2)][] = $path;
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$box.' '.$box.'" fill="currentColor" overflow="visible">';

        foreach ($groups as $opacity => $paths) {
            $svg .= '<path'.((float) $opacity < 1 ? ' opacity="'.$opacity.'"' : '').' d="'.Path::merge($paths)->toCompactSvg(self::PRECISION).'"/>';
        }

        return new Icon($svg.'</svg>');
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
