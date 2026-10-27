<?php

declare(strict_types=1);

namespace ExeQue\Qr\Manifest;

use ExeQue\Qr\Colors\Hex;
use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Icons\Icons;
use ExeQue\Qr\Registries\EyeRegistry;
use ExeQue\Qr\Registries\FilterRegistry;
use ExeQue\Qr\Registries\InnerEyeRegistry;
use ExeQue\Qr\Registries\ModuleRegistry;
use ExeQue\Qr\Registries\OuterEyeRegistry;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\EyeStyle;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Rendering\ScanSafety;
use ExeQue\Qr\Shapes\Contracts\Directional;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;
use ExeQue\Qr\Shapes\InnerEyes\Square as SquareInner;
use ExeQue\Qr\Shapes\Modules\Square;
use ExeQue\Qr\Shapes\OuterEyes\Square as SquareOuter;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Translation\Translator;
use Throwable;

/**
 * Everything a browser needs to know about the registered parts, as plain data: names, labels, unit
 * paths and the scan guards' limits, for pickers and instant checks. Codes themselves are still drawn
 * on the server.
 *
 * @internal Used through `Qr::manifest()`.
 */
final readonly class Manifest
{
    private const int PRECISION = 3;

    public function __construct(
        private ModuleRegistry $modules,
        private OuterEyeRegistry $outerEyes,
        private InnerEyeRegistry $innerEyes,
        private EyeRegistry $eyes,
        private FilterRegistry $filters,
        private Repository $config,
        private Translator $translator,
    ) {
    }

    /**
     * @return array{
     *     locale: string,
     *     modules: array<string, array{label: string, path: string, neighbors: bool, varies: bool}>,
     *     outerEyes: array<string, array{label: string, path: string, directional: bool}>,
     *     innerEyes: array<string, array{label: string, path: string, directional: bool}>,
     *     eyes: array<string, array{label: string, outer: string|null, inner: string|null}>,
     *     filters: array<string, array{label: string, module: bool, eye: bool, color: bool|null}>,
     *     scanSafety: array<string, mixed>,
     * }
     */
    public function toArray(?string $locale = null): array
    {
        $previous = $this->translator->getLocale();

        if ($locale !== null) {
            $this->translator->setLocale($locale);
        }

        try {
            return [
                'locale' => $this->translator->getLocale(),
                'modules' => $this->moduleEntries(),
                'outerEyes' => $this->partEntries($this->outerEyes),
                'innerEyes' => $this->partEntries($this->innerEyes),
                'eyes' => $this->eyeEntries(),
                'filters' => $this->filterEntries(),
                'scanSafety' => $this->scanSafety(),
            ];
        } finally {
            $this->translator->setLocale($previous);
        }
    }

    /**
     * Each module alone about its middle, (0, 0) in a 1x1 cell; whether it takes its neighbors into account,
     * and whether it is drawn otherwise from cell to cell, in which cases a drawing of one is a likeness only.
     *
     * @return array<string, array{label: string, path: string, neighbors: bool, varies: bool}>
     */
    private function moduleEntries(): array
    {
        $entries = [];
        $alone = fn (int $column): ModuleContext => new ModuleContext($column, 0, 21, Role::Data, [], crc32("{$column},0"));
        $surrounded = new ModuleContext(0, 0, 21, Role::Data, array_fill_keys(['-1,-1', '0,-1', '1,-1', '-1,0', '1,0', '-1,1', '0,1', '1,1'], true), crc32('0,0'));

        foreach ($this->modules->names() as $name) {
            $shape = $this->modules->get($name);
            $path = $shape->draw($alone(0))->toSvg(self::PRECISION);

            $entries[$name] = [
                'label' => $this->modules->label($name),
                'path' => $path,
                'neighbors' => $shape->draw($surrounded)->toSvg(self::PRECISION) !== $path,
                'varies' => $shape->draw($alone(5))->toSvg(self::PRECISION) !== $path,
            ];
        }

        return $entries;
    }

    /**
     * Each part about its middle: an outer eye within -3.5 to 3.5, an inner within -1.5 to 1.5; whether it
     * has one distinct corner, turned to point out on every corner of the code.
     *
     * @return array<string, array{label: string, path: string, directional: bool}>
     */
    private function partEntries(OuterEyeRegistry|InnerEyeRegistry $registry): array
    {
        $entries = [];

        foreach ($registry->names() as $name) {
            $shape = $registry->get($name);

            $entries[$name] = [
                'label' => $registry->label($name),
                'path' => $shape->path()->toSvg(self::PRECISION),
                'directional' => $shape instanceof Directional && $shape->isDirectional(),
            ];
        }

        return $entries;
    }

    /**
     * Each preset with its parts by name; null for a part no name stands for.
     *
     * @return array<string, array{label: string, outer: string|null, inner: string|null}>
     */
    private function eyeEntries(): array
    {
        $outers = $this->outerEyes->all();
        $inners = $this->innerEyes->all();
        $entries = [];

        foreach ($this->eyes->names() as $name) {
            $eye = $this->eyes->get($name);

            $entries[$name] = [
                'label' => $this->eyes->label($name),
                'outer' => self::nameOf($eye->qrOuterEye(), $outers),
                'inner' => self::nameOf($eye->qrInnerEye(), $inners),
            ];
        }

        return $entries;
    }

    /**
     * Each filter: whether it changes modules, eyes, or both, and whether it changes colors; null when it cannot
     * be made by its name to tell.
     *
     * @return array<string, array{label: string, module: bool, eye: bool, color: bool|null}>
     */
    private function filterEntries(): array
    {
        $entries = [];

        foreach ($this->filters->names() as $name) {
            $class = $this->filters->classOf($name);
            $filter = $this->sample($name);

            $entries[$name] = [
                'label' => $this->filters->label($name),
                'module' => is_a($filter ?? $class ?? '', ModuleFilter::class, true),
                'eye' => is_a($filter ?? $class ?? '', EyeFilter::class, true),
                'color' => $filter === null ? null : self::recolors($filter),
            ];
        }

        return $entries;
    }

    /**
     * The guards' limits as `config('qr.scan_safety')` sets them, in snake case, every one given.
     *
     * @return array<string, mixed>
     */
    private function scanSafety(): array
    {
        $safety = ScanSafety::fromConfig((array) $this->config->get('qr.scan_safety', []));

        return [
            'min_contrast' => $safety->minContrast,
            'on_violation' => $safety->onViolation->value,
            'inverted' => $safety->inverted?->value,
            'min_margin' => $safety->minMargin,
            'max_offset' => $safety->maxOffset,
            'min_scale' => $safety->minScale,
            'max_scale' => $safety->maxScale,
            'max_skew' => $safety->maxSkew,
            'max_skip' => $safety->maxSkip,
            'max_logo_share' => $safety->maxLogoShare,
            'outer_eye_scale' => $safety->outerEyeScale,
            'inner_eye_scale' => $safety->innerEyeScale,
            'eye_rotation_warning' => $safety->eyeRotationWarning,
            // What a code without a background is read against.
            'assumed_background' => '#ffffff',
        ];
    }

    /**
     * The filter with the options its icon is drawn with, which show what it does, or by its name alone;
     * null when it cannot be made.
     */
    private function sample(string $name): ?NamedFilter
    {
        try {
            return $this->filters->get($name, Icons::FILTER_OPTIONS[$name] ?? []);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Whether a filter changes the paint of any module or eye part of a small sample.
     */
    private static function recolors(NamedFilter $filter): bool
    {
        $black = new Hex('#000000');

        if ($filter instanceof ModuleFilter) {
            $base = new ModuleStyle(new Square(), $black);

            for ($cell = 0; $cell < 25; $cell++) {
                $context = new ModuleContext($cell % 5, intdiv($cell, 5), 5, Role::Data, [], crc32(($cell % 5).','.intdiv($cell, 5)));

                if ($filter->module($context, $base)->paint != $black) {
                    return true;
                }
            }
        }

        if ($filter instanceof EyeFilter) {
            $base = EyeStyle::of(new SquareOuter(), new SquareInner());

            foreach (Corner::cases() as $corner) {
                $eye = $filter->eye(EyeContext::of($corner, 21, $black), $base);

                if ($eye->outer->paint !== null || $eye->inner->paint !== null) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<string, OuterEyeShape|InnerEyeShape> $shapes
     */
    private static function nameOf(OuterEyeShape|InnerEyeShape $shape, array $shapes): ?string
    {
        $name = array_search($shape, $shapes, false);

        return is_string($name) ? $name : null;
    }
}
