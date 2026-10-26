<?php

declare(strict_types=1);

namespace ExeQue\Qr\Validation;

use Closure;
use ExeQue\Qr\Builders\Resolve;
use ExeQue\Qr\Exceptions\InvalidRuleException;
use ExeQue\Qr\Filters\Enums\Filter;
use ExeQue\Qr\Logos\Enums\LogoShape;
use ExeQue\Qr\Logos\Logo;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\ScanSafety;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Shapes\Enums\InnerEye;
use ExeQue\Qr\Shapes\Enums\Module;
use ExeQue\Qr\Shapes\Enums\OuterEye;
use ExeQue\Qr\Validation\Rules\EyePreset;
use ExeQue\Qr\Validation\Rules\FilterEntry;
use ExeQue\Qr\Validation\Rules\KnownKeys;
use ExeQue\Qr\Validation\Rules\Options;
use ExeQue\Qr\Validation\Rules\Scannable;
use ExeQue\Qr\Validation\Rules\ShapeName;
use ExeQue\Qr\Validation\Rules\ShapeOptions;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use SplFileInfo;

/**
 * Validation for a style taken from input, in a form request or a Livewire form. A field
 * is allowed once its method is called, narrowed by its arguments; anything else in the
 * input is refused. Use it as one rule (`'style' => StyleRule::defaults()`), or spread as
 * a rule per field (`...StyleRule::defaults()->for('style')`), and turn what passed into a
 * style with `toStyle()`.
 */
final readonly class StyleRule implements ValidationRule
{
    /**
     * The fields `only()` and `except()` name.
     */
    public const array FIELDS = ['modules', 'eyes', 'background', 'margin', 'filters', 'logo'];

    private const string CONTENT = 'https://example.com/qr/ABCD1234';

    /**
     * @param array<string, array<string, mixed>> $fields The allowed fields and how each is narrowed.
     * @param array<string, array<string, array<string, array{float|int, float|int}>>> $shapeRanges Option ranges by shape kind and name.
     * @param array<string, array<string, array{float|int, float|int}>> $filterRanges Option ranges by filter name.
     * @param array{safety: ScanSafety|null, content: string, base: Style|string|null}|null $scannable
     */
    private function __construct(
        private array $fields = [],
        private ?PaintRule $paint = null,
        private array $shapeRanges = [],
        private array $filterRanges = [],
        private ?array $scannable = null,
    ) {
    }

    /**
     * Rules with nothing allowed yet: each field's method allows it.
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Rules with every field allowed but the logo, eyes by corner included, each within the scan guards' limits.
     */
    public static function everything(): self
    {
        return self::make()->modules()->eyes(corners: true)->outer()->inner()->background()->margin()->filters();
    }

    /**
     * The application's rules, as set with useDefaults(); everything() until set.
     */
    public static function defaults(): self
    {
        $callback = self::stored();

        return $callback === null ? self::everything() : $callback();
    }

    /**
     * Sets the application's rules, such as in a service provider, with a callback that makes them.
     *
     * @param Closure(): self $callback
     */
    public static function useDefaults(Closure $callback): void
    {
        self::stored($callback);
    }

    /**
     * Only these of the fields allowed so far.
     */
    public function only(string ...$fields): self
    {
        self::checkFields($fields);

        return clone($this, ['fields' => array_filter($this->fields, fn (string $field): bool => in_array(self::group($field), $fields, true), ARRAY_FILTER_USE_KEY)]);
    }

    /**
     * All the fields allowed so far but these.
     */
    public function except(string ...$fields): self
    {
        self::checkFields($fields);

        return clone($this, ['fields' => array_filter($this->fields, fn (string $field): bool => ! in_array(self::group($field), $fields, true), ARRAY_FILTER_USE_KEY)]);
    }

    /**
     * The modules: their shape, paint, rotation, size (`scaleX`, `scaleY`) and opacity.
     *
     * @param array{float|int, float|int}|false|null $rotation Degrees; false refuses it, null keeps the default range.
     * @param array{float|int, float|int}|false|null $size A share of the shape's size; within the scan guards by default.
     * @param array{float|int, float|int}|false|null $opacity
     * @param bool $options Whether the shape's own options (`options`) may be given, not only its name.
     */
    public function modules(
        ?Allow $shapes = null,
        ?PaintRule $paint = null,
        array|false|null $rotation = null,
        array|false|null $size = null,
        array|false|null $opacity = null,
        bool $options = false,
    ): self {
        return $this->with('modules', compact('shapes', 'paint', 'rotation', 'size', 'opacity', 'options'));
    }

    /**
     * Whole eyes by preset (`eyes.preset`), and a paint for both parts (`eyes.paint`); with
     * `corners`, each corner apart too (`eyes.corners.top-left`), all or the corners listed.
     *
     * @param list<Corner>|bool $corners
     */
    public function eyes(Allow|false|null $presets = null, array|bool $corners = false, ?PaintRule $paint = null): self
    {
        return $this->with('eyes', compact('presets', 'corners', 'paint'));
    }

    /**
     * The outer eyes apart (`eyes.outer`): shape, paint, rotation, size and opacity.
     *
     * @param array{float|int, float|int}|false|null $rotation
     * @param array{float|int, float|int}|false|null $size Within the scan guards by default.
     * @param array{float|int, float|int}|false|null $opacity
     */
    public function outer(
        ?Allow $shapes = null,
        ?PaintRule $paint = null,
        array|false|null $rotation = null,
        array|false|null $size = null,
        array|false|null $opacity = null,
        bool $options = false,
    ): self {
        return $this->with('outer', compact('shapes', 'paint', 'rotation', 'size', 'opacity', 'options'));
    }

    /**
     * The inner eyes apart (`eyes.inner`): shape, paint, rotation, size and opacity.
     *
     * @param array{float|int, float|int}|false|null $rotation
     * @param array{float|int, float|int}|false|null $size Within the scan guards by default.
     * @param array{float|int, float|int}|false|null $opacity
     */
    public function inner(
        ?Allow $shapes = null,
        ?PaintRule $paint = null,
        array|false|null $rotation = null,
        array|false|null $size = null,
        array|false|null $opacity = null,
        bool $options = false,
    ): self {
        return $this->with('inner', compact('shapes', 'paint', 'rotation', 'size', 'opacity', 'options'));
    }

    /**
     * The paint every module and eye paint takes, unless their own methods give another.
     */
    public function paint(PaintRule $paint): self
    {
        return clone($this, ['paint' => $paint]);
    }

    /**
     * The background: any paint, or null for none, unless narrowed.
     */
    public function background(?PaintRule $paint = null): self
    {
        return $this->with('background', ['paint' => $paint ?? PaintRule::any()->nullable()]);
    }

    /**
     * The quiet zone in modules; from the scan guards' least margin to 16 by default.
     *
     * @param array{int, int}|null $range
     */
    public function margin(?array $range = null): self
    {
        return $this->with('margin', ['range' => $range]);
    }

    /**
     * Filters (`filters`), as `['name' => ..., 'options' => [...]]`, at most `$max` of them.
     */
    public function filters(?Allow $filters = null, int $max = 8): self
    {
        return $this->with('filters', ['allow' => $filters ?? Allow::all(), 'max' => $max]);
    }

    /**
     * Tighter ranges for a filter's options, by name: `filter(Filter::MixedRotation, min: [-30, 0])`.
     *
     * @param array{float|int, float|int} ...$ranges
     */
    public function filter(Filter|string $filter, array ...$ranges): self
    {
        $name = $filter instanceof Filter ? $filter->value : $filter;
        /** @var array<string, array{float|int, float|int}> $ranges Named arguments, by option. */

        return clone($this, ['filterRanges' => [...$this->filterRanges, $name => [...$this->filterRanges[$name] ?? [], ...$ranges]]]);
    }

    /**
     * Tighter ranges for a shape's options: `shape(Module::Rounded, radius: [0, 0.4])`, or by the name
     * it is registered under, with its kind: `shape('brand-dots', kind: 'module', diameter: [0.6, 0.9])`.
     *
     * @param 'module'|'outer'|'inner' $kind For a name; an enum case says its own.
     * @param array{float|int, float|int} ...$ranges
     */
    public function shape(Module|OuterEye|InnerEye|string $shape, string $kind = 'module', array ...$ranges): self
    {
        $kind = match (true) {
            $shape instanceof Module => 'module',
            $shape instanceof OuterEye => 'outer',
            $shape instanceof InnerEye => 'inner',
            default => $kind,
        };
        $name = is_string($shape) ? $shape : $shape->value;

        $current = $this->shapeRanges[$kind][$name] ?? [];
        /** @var array<string, array{float|int, float|int}> $ranges Named arguments, by option. */

        return clone($this, ['shapeRanges' => array_replace_recursive($this->shapeRanges, [$kind => [$name => [...$current, ...$ranges]]])]);
    }

    /**
     * The logo's settings (`logo`); never its image, which comes apart to `toStyle()`.
     *
     * @param array{float|int, float|int}|false|null $size A share of the code's width, or 'auto'.
     * @param array{int, int}|false|null $padding Modules.
     * @param PaintRule|false|null $plate Any color by default.
     * @param array{float|int, float|int}|false|null $plateRadius Modules.
     * @param array{float|int, float|int}|false|null $position Modules from the code's top-left, both ways; refused by default.
     */
    public function logo(
        array|false|null $size = null,
        array|false|null $padding = null,
        ?Allow $shapes = null,
        PaintRule|false|null $plate = null,
        array|false|null $plateRadius = null,
        array|false|null $position = false,
    ): self {
        return $this->with('logo', compact('size', 'padding', 'shapes', 'plate', 'plateRadius', 'position'));
    }

    /**
     * Refuses a style the scan guards would have to change or warn about when it is drawn, with
     * the guards given or else the configured ones; nothing is refused for guards turned off.
     *
     * @param string $content What the code is checked with; the logo and margin guards depend on its size.
     * @param Style|string|null $base The style the input goes over, as given to toStyle(); the default style when null.
     */
    public function scannable(?ScanSafety $safety = null, string $content = self::CONTENT, Style|string|null $base = null): self
    {
        return clone($this, ['scannable' => ['safety' => $safety, 'content' => $content, 'base' => $base]]);
    }

    /**
     * A Laravel rule per field, under the attribute the style is given as.
     *
     * @return array<string, list<mixed>>
     */
    public function for(string $attribute): array
    {
        $safety = $this->scannable['safety'] ?? ScanSafety::fromConfig((array) config('qr.scan_safety', []));
        $rules = [$attribute => ['sometimes', new KnownKeys(array_values(array_unique(array_map(self::group(...), array_keys($this->fields)))))]];

        if ($this->scannable !== null) {
            $rules[$attribute][] = new Scannable(clone($this, ['scannable' => null]), $this->scannable['safety'], $this->scannable['content'], $this->scannable['base']);
        }

        if (isset($this->fields['modules'])) {
            $rules = [...$rules, ...$this->part("{$attribute}.modules", 'module', $this->fields['modules'], [$safety->minScale ?? 0.1, $safety->maxScale ?? 2.0])];
        }

        if (isset($this->fields['eyes']) || isset($this->fields['outer']) || isset($this->fields['inner'])) {
            $rules = [...$rules, ...$this->eyeRules($attribute, $safety)];
        }

        if (isset($this->fields['background'])) {
            $rules["{$attribute}.background"] = ['sometimes', $this->fields['background']['paint']];
        }

        if (isset($this->fields['margin'])) {
            $rules["{$attribute}.margin"] = self::leaf('integer', ...Options::between($this->fields['margin']['range'] ?? [$safety->minMargin ?? 0, 16]));
        }

        if (isset($this->fields['filters'])) {
            $rules["{$attribute}.filters"] = ['sometimes', 'list', 'max:'.$this->fields['filters']['max']];
            $rules["{$attribute}.filters.*"] = ['filled', new FilterEntry($this->fields['filters']['allow'], $this->filterRanges, $this->shapePolicies())];
        }

        if (isset($this->fields['logo'])) {
            $rules = [...$rules, ...$this->logoRules("{$attribute}.logo", $this->fields['logo'])];
        }

        return $rules;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Under its own path, so the rules find it under a nested or wildcard attribute too.
        $data = [];
        data_set($data, $attribute, $value);

        foreach (app(Factory::class)->make($data, $this->for($attribute))->errors()->all() as $message) {
            $fail($message);
        }
    }

    /**
     * A style from validated input, over the base given, the default style, or a named style.
     *
     * @param array<array-key, mixed> $validated
     * @param Logo|SplFileInfo|null $logo The logo's image, for input with logo settings.
     */
    public static function toStyle(array $validated, Style|string|null $base = null, Logo|SplFileInfo|null $logo = null): Style
    {
        $style = match (true) {
            $base === null => Style::default(),
            $base instanceof Style => $base,
            default => app(Resolve::class)->style($base),
        };

        return StyleInput::apply($validated, $style, $logo instanceof SplFileInfo ? Logo::fromFile($logo->getPathname()) : $logo);
    }

    /**
     * A style as input, such as to fill a form with: a preset where a corner's parts are one, shared
     * eye settings where the corners agree, and settings at their defaults left out.
     *
     * @return array<string, mixed>
     */
    public static function toPayload(Style $style): array
    {
        return StyleInput::of($style);
    }

    /**
     * The rules for shapes of each kind allowed, which shapes in filters' options follow too.
     *
     * @return array<string, array{allow: Allow, options: bool, ranges: array<string, array<string, array{float|int, float|int}>>}>
     */
    private function shapePolicies(): array
    {
        $policies = [];

        foreach (['modules' => 'module', 'outer' => 'outer', 'inner' => 'inner'] as $field => $kind) {
            if (isset($this->fields[$field])) {
                $policies[$kind] = [
                    'allow' => $this->fields[$field]['shapes'] ?? Allow::all(),
                    'options' => (bool) $this->fields[$field]['options'],
                    'ranges' => $this->shapeRanges[$kind] ?? [],
                ];
            }
        }

        return $policies;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function with(string $field, array $settings): self
    {
        return clone($this, ['fields' => [...$this->fields, $field => $settings]]);
    }

    /**
     * Holds the callback that makes the application's rules, setting it when given one.
     *
     * @param (Closure(): self)|null $callback
     *
     * @return (Closure(): self)|null
     */
    private static function stored(?Closure $callback = null): ?Closure
    {
        static $stored = null;

        if ($callback !== null) {
            $stored = $callback;
        }

        return $stored;
    }

    /**
     * @param array<array-key, string> $fields
     */
    private static function checkFields(array $fields): void
    {
        foreach ($fields as $field) {
            if (! in_array($field, self::FIELDS, true)) {
                throw InvalidRuleException::unknownField($field, self::FIELDS);
            }
        }
    }

    /**
     * The `only()`/`except()` field a configured one belongs to.
     */
    private static function group(string $field): string
    {
        return in_array($field, ['outer', 'inner'], true) ? 'eyes' : $field;
    }

    /**
     * @return list<mixed>
     */
    private static function leaf(mixed ...$rules): array
    {
        return ['sometimes', 'filled', ...array_values($rules)];
    }

    /**
     * Rules for the modules or an eye part.
     *
     * @param 'module'|'outer'|'inner' $kind
     * @param array<string, mixed> $settings
     * @param array{float|int, float|int} $size
     *
     * @return array<string, list<mixed>>
     */
    private function part(string $prefix, string $kind, array $settings, array $size): array
    {
        $ranges = array_filter([
            'rotation' => $settings['rotation'] ?? [-180, 180],
            'scaleX' => $settings['size'] ?? $size,
            'scaleY' => $settings['size'] ?? $size,
            'opacity' => $settings['opacity'] ?? [0, 1],
        ], fn (array|false $range): bool => $range !== false);
        $keys = ['shape', ...($settings['options'] ? ['options'] : []), 'paint', ...array_keys($ranges)];

        $rules = [
            $prefix => ['sometimes', new KnownKeys($keys)],
            "{$prefix}.shape" => ["required_with:{$prefix}.options", 'filled', new ShapeName($kind, $settings['shapes'] ?? Allow::all())],
            "{$prefix}.paint" => ['sometimes', $settings['paint'] ?? $this->paint ?? PaintRule::any()],
        ];

        if ($settings['options']) {
            $rules["{$prefix}.options"] = ['sometimes', 'array', new ShapeOptions($kind, $this->shapeRanges[$kind] ?? [])];
        }

        foreach ($ranges as $key => $range) {
            $rules["{$prefix}.{$key}"] = self::leaf(...Options::between($range));
        }

        return $rules;
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function eyeRules(string $attribute, ScanSafety $safety): array
    {
        $eyes = $this->fields['eyes'] ?? ['presets' => false, 'corners' => false, 'paint' => null];
        /** @var list<Corner> $corners */
        $corners = match ($eyes['corners']) {
            true => Corner::cases(),
            false => [],
            default => array_values((array) $eyes['corners']),
        };

        $rules = $this->eyeLevel("{$attribute}.eyes", $eyes, $safety, array_map(fn (Corner $corner): string => $corner->value, $corners));

        foreach ($corners as $corner) {
            $rules = [...$rules, ...$this->eyeLevel("{$attribute}.eyes.corners.{$corner->value}", $eyes, $safety, null)];
        }

        return $rules;
    }

    /**
     * Rules for one level of the eyes: shared, or a corner's.
     *
     * @param array<string, mixed> $eyes
     * @param list<string>|null $corners The corners that may be set apart; null on a corner itself.
     *
     * @return array<string, list<mixed>>
     */
    private function eyeLevel(string $prefix, array $eyes, ScanSafety $safety, ?array $corners): array
    {
        $parts = array_values(array_filter(['outer', 'inner'], fn (string $part): bool => isset($this->fields[$part])));
        $keys = [...($eyes['presets'] !== false ? ['preset'] : []), ...$parts, 'paint', ...($corners ? ['corners'] : [])];
        $rules = [
            $prefix => ['sometimes', new KnownKeys($keys)],
            "{$prefix}.paint" => ['sometimes', $eyes['paint'] ?? $this->paint ?? PaintRule::any()],
        ];

        if ($eyes['presets'] !== false) {
            $prohibits = implode(',', array_map(fn (string $part): string => "{$prefix}.{$part}", $parts));
            $rules["{$prefix}.preset"] = self::leaf(...[...($parts !== [] ? ["prohibits:{$prohibits}"] : []), new EyePreset($eyes['presets'] ?? Allow::all())]);
        }

        foreach ($parts as $part) {
            $scale = ($part === 'outer' ? $safety->outerEyeScale : $safety->innerEyeScale) ?? [0.5, 1.5];
            $rules = [...$rules, ...$this->part("{$prefix}.{$part}", $part, $this->fields[$part], $scale)];
        }

        if ($corners) {
            $rules["{$prefix}.corners"] = ['sometimes', new KnownKeys($corners)];
        }

        return $rules;
    }

    /**
     * @param array<string, mixed> $logo
     *
     * @return array<string, list<mixed>>
     */
    private function logoRules(string $prefix, array $logo): array
    {
        $size = $logo['size'] ?? [0.05, 0.3];
        $shapes = ($logo['shapes'] ?? Allow::all())->of(array_map(fn (LogoShape $shape): string => $shape->value, LogoShape::cases()));
        $rules = array_filter([
            'size' => $size === false ? null : self::leaf(function (string $attribute, mixed $value, Closure $fail) use ($size): void {
                if ($value !== 'auto' && (! is_numeric($value) || $value < $size[0] || $value > $size[1])) {
                    $fail(trans('qr::validation.logo_size', ['min' => $size[0], 'max' => $size[1]]));
                }
            }),
            'padding' => $logo['padding'] === false ? null : self::leaf('integer', ...Options::between($logo['padding'] ?? [0, 4])),
            'shape' => self::leaf(Rule::in($shapes)),
            'plate' => $logo['plate'] === false ? null : ['sometimes', $logo['plate'] ?? PaintRule::colors()->nullable()],
            'plateRadius' => $logo['plateRadius'] === false ? null : self::leaf(...Options::between($logo['plateRadius'] ?? [0, 4])),
            'position' => $logo['position'] === false ? null : self::leaf('list', 'size:2'),
            'position.*' => $logo['position'] === false ? null : self::leaf(...Options::between($logo['position'] ?? [0, 177])),
        ], fn (?array $rules): bool => $rules !== null);

        $prefixed = [$prefix => ['sometimes', new KnownKeys(array_values(array_filter(array_keys($rules), fn (string $key): bool => ! str_contains($key, '.'))))]];

        foreach ($rules as $key => $rule) {
            $prefixed["{$prefix}.{$key}"] = $rule;
        }

        return $prefixed;
    }
}
