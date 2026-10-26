<?php

declare(strict_types=1);

namespace Workbench\App\Livewire\Concerns;

use BackedEnum;
use ExeQue\Qr\Builders\EyeBuilder;
use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Filters\Region;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\Enums\Violation;
use ExeQue\Qr\Rendering\ScanSafety;
use UnitEnum;

/**
 * The PHP that makes the code on screen, as an application would write it: settings at
 * their defaults left out.
 */
trait GeneratesCode
{
    private function php(): string
    {
        $imports = [Qr::class => true];
        $calls = [];

        $calls[] = "->errorCorrection('{$this->errorCorrection}')";
        $calls = [...$calls, ...($this->minVersion > 1 ? ["->minVersion({$this->minVersion})"] : [])];
        $calls = [...$calls, ...($this->margin !== 4 ? ["->margin({$this->margin})"] : [])];
        $calls[] = '->modules('.self::arguments([self::literal($this->module), ...$this->changedOptions('modules', $this->module, $this->moduleOptions)]).')';
        $calls[] = '->paint('.$this->paintPhp($this->paint, $imports, '    ').')';

        if ($this->transparent) {
            $calls[] = '->background(null)';
        } elseif ($this->background != self::paintSpec('#ffffff')) {
            $calls[] = '->background('.$this->paintPhp($this->background, $imports, '    ').')';
        }

        $calls = [...$calls, ...($this->eyeMode === 'preset'
            ? ($this->eye === 'square' ? [] : ['->eyes('.self::literal($this->eye).')'])
            : [
                '->outer('.self::arguments([self::literal($this->outer), ...$this->changedOptions('outerEyes', $this->outer, $this->outerOptions)]).')',
                '->inner('.self::arguments([self::literal($this->inner), ...$this->changedOptions('innerEyes', $this->inner, $this->innerOptions)]).')',
            ])];

        foreach ($this->corners as $corner => $settings) {
            $eye = array_filter([
                $settings['outer'] === '' ? null : '->outer('.self::literal($settings['outer']).')',
                $settings['inner'] === '' ? null : '->inner('.self::literal($settings['inner']).')',
                (float) $settings['rotate'] === 0.0 ? null : '->rotate('.self::literal((float) $settings['rotate']).')',
                $settings['outerPaint'] === '' ? null : '->outerPaint('.self::literal($settings['outerPaint']).')',
                $settings['innerPaint'] === '' ? null : '->innerPaint('.self::literal($settings['innerPaint']).')',
            ]);

            if ($eye !== []) {
                $imports[Corner::class] = true;
                $imports[EyeBuilder::class] = true;
                $calls[] = '->eye('.self::case(Corner::from($corner)).', fn (EyeBuilder $eye): EyeBuilder => $eye'.implode('', $eye).')';
            }
        }

        foreach ($this->filters as $filter) {
            $calls[] = '->filter('.$this->filterPhp($filter, $imports).')';
        }

        $calls = [...$calls, ...($this->mirrored ? ['->mirrored()'] : [])];
        $calls = [...$calls, ...$this->safetyPhp($imports)];

        if ($this->logo !== '') {
            $calls[] = '->logo('.self::arguments([
                $this->logo === self::UPLOADED
                    ? "storage_path('app/".addcslashes($this->upload?->getClientOriginalName() ?? 'logo.png', "'\\")."')"
                    : "resource_path('logos/{$this->logo}')",
                ...($this->logoAuto ? ["size: 'auto'"] : ($this->logoSize !== 0.2 ? ['size: '.self::literal($this->logoSize)] : [])),
                ...($this->logoPadding !== 1 ? ["padding: {$this->logoPadding}"] : []),
                ...($this->logoShape !== 'square' ? ['shape: '.self::literal($this->logoShape)] : []),
                ...($this->logoPlated ? ['plate: '.$this->paintPhp($this->logoPlate, $imports, '    ')] : []),
                ...($this->logoPlated && (float) $this->logoPlateRadius !== 1.0 ? ['plateRadius: '.self::literal((float) $this->logoPlateRadius)] : []),
            ]).')';
        }

        $calls[] = match ($this->format) {
            'svg' => '->svg('.self::arguments([
                ...($this->currentColor ? ['currentColor: true'] : []),
                ...($this->compact ? [] : ['compact: false']),
                ...($this->precision !== 3 ? ["precision: {$this->precision}"] : []),
            ]).');',
            default => "->{$this->format}(".self::arguments([
                (string) $this->size,
                ...($this->dpi === null ? [] : ["dpi: {$this->dpi}"]),
                ...($this->format !== 'png' && $this->quality !== 90 ? ["quality: {$this->quality}"] : []),
                ...($this->format === 'png' && $this->compressionLevel !== 6 ? ["compressionLevel: {$this->compressionLevel}"] : []),
                ...($this->driver !== 'auto' ? ['driver: '.self::literal($this->driver)] : []),
            ]).');',
        };

        $uses = array_keys($imports);
        sort($uses);

        return implode("\n", array_map(fn (string $class): string => "use {$class};", $uses))
            ."\n\n\$qr = Qr::make(".self::literal($this->content).')'
            .implode('', array_map(fn (string $call): string => "\n    {$call}", $calls));
    }

    /**
     * @param array{name: string, options: array<string, mixed>, roles: list<string>, region: bool, regionX: float, regionY: float, regionRadius: float, corners: list<string>, gradient: array<string, mixed>} $filter
     * @param array<string, true> $imports
     */
    private function filterPhp(array $filter, array &$imports): string
    {
        $class = (string) Qr::filters()->classOf($filter['name']);
        $imports[$class] = true;
        $arguments = [];

        foreach ($this->filterFields($filter['name']) as $name => $field) {
            if ($field['kind'] === 'gradient') {
                $arguments[] = "{$name}: ".$this->layerPhp($filter['gradient'], $imports, '        ');

                continue;
            }

            $value = $filter['options'][$name] ?? $field['value'];

            if (! $field['required'] && ($value === $field['value'] || ($field['kind'] !== 'list' && self::cast($value, $field['kind']) === self::cast($field['value'], $field['kind'])))) {
                continue;
            }

            $value = self::cast($value, $field['kind']);

            if ($value instanceof UnitEnum) {
                $imports[$value::class] = true;
            }

            $arguments[] = "{$name}: ".self::literal($value);
        }

        $scopes = [];

        if ($filter['roles'] !== []) {
            $imports[Role::class] = true;
            $scopes[] = '->only('.implode(', ', array_map(fn (string $role): string => self::case(Role::from($role)), $filter['roles'])).')';
        }

        if ($filter['region']) {
            $imports[Region::class] = true;
            $scopes[] = '->in(Region::circle('.implode(', ', array_map(fn (mixed $value): string => self::literal((float) $value), [$filter['regionX'], $filter['regionY'], $filter['regionRadius']])).'))';
        }

        if ($filter['corners'] !== []) {
            $imports[Corner::class] = true;
            $scopes[] = '->corners('.implode(', ', array_map(fn (string $corner): string => self::case(Corner::from($corner)), $filter['corners'])).')';
        }

        $made = 'new '.class_basename($class).'('.self::arguments($arguments).')';

        return $scopes === [] ? $made : "({$made})".implode('', $scopes);
    }

    /**
     * @param array<string, true> $imports
     *
     * @return list<string>
     */
    private function safetyPhp(array &$imports): array
    {
        if (! $this->guards) {
            $imports[ScanSafety::class] = true;

            return ['->scanSafety(ScanSafety::off())'];
        }

        $defaults = get_object_vars(new ScanSafety());
        $arguments = [];

        foreach ($this->limits as $name => $value) {
            $value = $value === '' || $value === null ? null : (float) $value;

            if ($value !== (is_numeric($defaults[$name]) ? (float) $defaults[$name] : $defaults[$name])) {
                $arguments[] = "{$name}: ".($name === 'minMargin' && $value !== null ? (string) (int) $value : self::literal($value));
            }
        }

        if ($this->violation !== 'throw') {
            $imports[Violation::class] = true;
            $arguments[] = 'onViolation: '.self::case(Violation::from($this->violation));
        }

        if ($arguments === []) {
            return [];
        }

        $imports[ScanSafety::class] = true;

        return ['->scanSafety(new ScanSafety('.self::arguments($arguments).'))'];
    }

    /**
     * A shape's options that differ from its defaults, as named arguments.
     *
     * @param array<string, mixed> $options
     *
     * @return list<string>
     */
    private function changedOptions(string $registry, string $name, array $options): array
    {
        $defaults = $this->shapeOptions($registry, $name);
        $arguments = [];

        foreach ($this->typed($registry, $name, $options) as $key => $value) {
            if ($value != $defaults[$key]) {
                $arguments[] = "{$key}: ".self::literal($value instanceof BackedEnum ? $value->value : $value);
            }
        }

        return $arguments;
    }

    /**
     * @param list<string> $arguments
     */
    private static function arguments(array $arguments): string
    {
        return implode(', ', $arguments);
    }

    private static function literal(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_float($value) => str_contains($formatted = rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.'), '.') ? $formatted : "{$formatted}.0",
            is_int($value) => (string) $value,
            $value instanceof UnitEnum => self::case($value),
            is_array($value) => '['.implode(', ', array_map(self::literal(...), $value)).']',
            default => "'".addcslashes((string) $value, "'\\")."'",
        };
    }

    private static function case(UnitEnum $case): string
    {
        return class_basename($case).'::'.$case->name;
    }
}
