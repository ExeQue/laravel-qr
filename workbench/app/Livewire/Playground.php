<?php

declare(strict_types=1);

namespace Workbench\App\Livewire;

use BackedEnum;
use ExeQue\Qr\Builders\EyeBuilder;
use ExeQue\Qr\Builders\EyesBuilder;
use ExeQue\Qr\Builders\PendingQr;
use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Exceptions\QrExceptionInterface;
use ExeQue\Qr\Exceptions\ScanSafetyException;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Filters\Enums\Property;
use ExeQue\Qr\Filters\Region;
use ExeQue\Qr\Gradients\Enums\GradientBox;
use ExeQue\Qr\Logos\Logo;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\Enums\Violation;
use ExeQue\Qr\Rendering\GuardAdjustment;
use ExeQue\Qr\Rendering\ScanSafety;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;
use TypeError;
use ValueError;
use Workbench\App\Livewire\Concerns\BuildsPaints;
use Workbench\App\Livewire\Concerns\GeneratesCode;

/**
 * A playground for the generator: every setting on the left, the code on the right, rendered
 * on the server on every change. Shapes and filters show the options they have, so new ones
 * appear here without changes.
 */
class Playground extends Component
{
    use BuildsPaints;
    use GeneratesCode;
    use WithFileUploads;

    // The logo select's value for an image uploaded here.
    private const string UPLOADED = 'uploaded';

    // Filters better set up through a filter's scope, which every filter here has.
    private const array WRAPPERS = ['by-role', 'region', 'compound'];

    public string $content = 'https://example.com/e/ABCD1234';

    public string $errorCorrection = 'Q';

    public int $minVersion = 1;

    public int $margin = 4;

    public string $module = 'square';

    /**
     * @var array<string, mixed>
     */
    public array $moduleOptions = [];

    public string $eyeMode = 'preset';

    public string $eye = 'square';

    public string $outer = 'square';

    /**
     * @var array<string, mixed>
     */
    public array $outerOptions = [];

    public string $inner = 'square';

    /**
     * @var array<string, mixed>
     */
    public array $innerOptions = [];

    public bool $mirrored = false;

    /**
     * @var array<string, array{outer: string, inner: string, rotate: float, outerPaint: string, innerPaint: string}>
     */
    public array $corners = [];

    /**
     * @var array<string, mixed>
     */
    public array $paint = [];

    public bool $transparent = false;

    /**
     * @var array<string, mixed>
     */
    public array $background = [];

    /**
     * @var list<array{name: string, options: array<string, mixed>, roles: list<string>, region: bool, regionX: float, regionY: float, regionRadius: float, corners: list<string>, gradient: array<string, mixed>}>
     */
    public array $filters = [];

    public string $addFilter = 'mixed-rotation';

    public string $logo = '';

    #[Validate('nullable|file|max:2048|mimes:png,jpg,jpeg,webp,gif,svg')]
    public ?TemporaryUploadedFile $upload = null;

    public float $logoSize = 0.2;

    public bool $logoAuto = false;

    public int $logoPadding = 1;

    public string $logoShape = 'square';

    public bool $logoPlated = false;

    /**
     * @var array<string, mixed>
     */
    public array $logoPlate = [];

    public float $logoPlateRadius = 1;

    public bool $guards = true;

    public string $violation = 'throw';

    /**
     * @var array<string, float|int|null>
     */
    public array $limits = [];

    public string $format = 'svg';

    public int $size = 480;

    public string $driver = 'auto';

    public ?int $dpi = null;

    public int $quality = 90;

    public int $compressionLevel = 6;

    public bool $currentColor = false;

    public bool $compact = true;

    public int $precision = 3;

    public bool $checkScan = true;

    /**
     * The last code drawn, shown while the settings cannot draw one.
     *
     * @var array<string, mixed>
     */
    #[Locked]
    public array $last = [];

    public function mount(): void
    {
        $this->paint = self::paintSpec('#1c1917');
        $this->background = self::paintSpec('#ffffff');
        $this->logoPlate = self::paintSpec('#ffffff');
        // A plate's gradient reads best across the plate, not the whole code.
        $this->logoPlate['layers'][0]['box'] = GradientBox::Shape->value;
        $this->moduleOptions = $this->shapeOptions('modules', $this->module);
        $this->outerOptions = $this->shapeOptions('outerEyes', $this->outer);
        $this->innerOptions = $this->shapeOptions('innerEyes', $this->inner);
        $this->limits = array_intersect_key(get_object_vars(new ScanSafety()), array_flip(['minContrast', 'minMargin', 'maxOffset', 'minScale', 'maxScale', 'maxSkew', 'maxSkip', 'maxLogoShare', 'eyeRotationWarning']));

        foreach (Corner::cases() as $corner) {
            $this->corners[$corner->value] = ['outer' => '', 'inner' => '', 'rotate' => 0.0, 'outerPaint' => '', 'innerPaint' => ''];
        }
    }

    public function updatedModule(string $module): void
    {
        $this->moduleOptions = $this->shapeOptions('modules', $module);
    }

    public function updatedOuter(string $outer): void
    {
        $this->outerOptions = $this->shapeOptions('outerEyes', $outer);
    }

    public function updatedInner(string $inner): void
    {
        $this->innerOptions = $this->shapeOptions('innerEyes', $inner);
    }

    public function updatedUpload(): void
    {
        $this->validateOnly('upload');
        $this->logo = self::UPLOADED;
    }

    public function addSelectedFilter(): void
    {
        $this->filters[] = [
            'name' => $this->addFilter,
            'options' => array_map(fn (array $field): mixed => $field['value'], $this->filterFields($this->addFilter)),
            'roles' => [],
            'region' => false,
            'regionX' => 14.5,
            'regionY' => 14.5,
            'regionRadius' => 6.0,
            'corners' => [],
            'gradient' => self::layerSpec('#be123c', 'linear'),
        ];
    }

    public function removeFilter(int $index): void
    {
        unset($this->filters[$index]);
        $this->filters = array_values($this->filters);
    }

    public function moveFilter(int $index, int $by): void
    {
        $to = $index + $by;

        if (! isset($this->filters[$index], $this->filters[$to])) {
            return;
        }

        [$this->filters[$index], $this->filters[$to]] = [$this->filters[$to], $this->filters[$index]];
    }

    public function render(): View
    {
        $started = hrtime(true);
        $data = [
            'error' => null,
            'code' => null,
            'image' => null,
            'scans' => null,
            'moduleNames' => Qr::modules()->names(),
            'outerNames' => Qr::outerEyes()->names(),
            'innerNames' => Qr::innerEyes()->names(),
            'eyeNames' => Qr::eyes()->names(),
            'moduleChoices' => $this->choices('modules', $this->module),
            'outerChoices' => $this->choices('outerEyes', $this->outer),
            'innerChoices' => $this->choices('innerEyes', $this->inner),
            'filterNames' => array_values(array_diff(Qr::filters()->names(), self::WRAPPERS)),
            'filterFields' => array_map(fn (array $filter): array => $this->filterFields($filter['name']), $this->filters),
            'eyeFilters' => array_map(fn (array $filter): bool => is_subclass_of((string) Qr::filters()->classOf($filter['name']), EyeFilter::class), $this->filters),
            'roles' => array_map(fn (Role $role): string => $role->value, Role::cases()),
            'properties' => array_map(fn (Property $property): string => $property->value, Property::cases()),
            'logos' => self::logos(),
            'php' => null,
            'warning' => null,
        ];

        try {
            $data['php'] = $this->php();
        } catch (Throwable) {
            // The PHP follows the code; when the settings do not make one, neither does it.
        }

        try {
            $data = [...$data, ...$this->draw($this->code(), $started)];
        } catch (ScanSafetyException $exception) {
            // Drawn anyway, without the guards: the playground is for seeing what a style does.
            $data = [...$data, ...$this->draw($this->code()->scanSafety(ScanSafety::off()), $started)];
            $data['warning'] = $exception->getMessage().' Drawn without the scan guards, so it may not scan.';
        } catch (QrExceptionInterface|TypeError|ValueError|InvalidArgumentException $exception) {
            $data = [...$data, ...$this->last, 'error' => $exception->getMessage()];
        }

        if ($data['error'] === null) {
            $this->last = array_intersect_key($data, array_flip(['code', 'image', 'bytes', 'modules', 'adjustments', 'milliseconds', 'scans']));
        }

        return view('livewire.playground', $data);
    }

    /**
     * The code drawn in the format asked for, and what there is to say about it.
     *
     * @return array<string, mixed>
     */
    private function draw(PendingQr $code, int $started): array
    {
        $output = match ($this->format) {
            'svg' => $code->svg(currentColor: $this->currentColor, compact: $this->compact, precision: $this->precision),
            'jpeg' => $code->jpeg($this->size, dpi: $this->dpi, quality: $this->quality, driver: $this->driver),
            'webp' => $code->webp($this->size, dpi: $this->dpi, quality: $this->quality, driver: $this->driver),
            default => $code->png($this->size, dpi: $this->dpi, driver: $this->driver, compressionLevel: $this->compressionLevel),
        };
        $scene = $code->scene();

        return [
            'code' => $this->format === 'svg' ? $output->contents() : null,
            'image' => $this->format === 'svg' ? null : $output->toDataUri(),
            'bytes' => strlen($output->contents()),
            'modules' => $scene->size - 2 * $scene->margin,
            'adjustments' => array_map(fn (GuardAdjustment $adjustment): string => trim("{$adjustment->guard}: {$adjustment->detail}"), $scene->adjustments),
            'milliseconds' => round((hrtime(true) - $started) / 1e6, 1),
            'scans' => $this->checkScan ? $this->scans($code) : null,
        ];
    }

    private function code(): PendingQr
    {
        $code = Qr::make($this->content)
            ->withoutCache()
            ->errorCorrection($this->errorCorrection)
            ->minVersion(max(1, min(40, $this->minVersion)))
            ->margin($this->margin)
            ->modules($this->module, ...$this->typed('modules', $this->module, $this->moduleOptions))
            ->paint($this->paintOf($this->paint))
            ->background($this->transparent ? null : $this->paintOf($this->background));

        $code = $this->eyeMode === 'preset'
            ? $code->eyes($this->eye)
            : $code
                ->outer($this->outer, ...$this->typed('outerEyes', $this->outer, $this->outerOptions))
                ->inner($this->inner, ...$this->typed('innerEyes', $this->inner, $this->innerOptions));

        $code = $code->eyes(fn (EyesBuilder $eyes): EyesBuilder => array_reduce(
            Corner::cases(),
            fn (EyesBuilder $eyes, Corner $corner): EyesBuilder => $eyes->corner($corner, fn (EyeBuilder $eye): EyeBuilder => $this->corner($eye, $this->corners[$corner->value])),
            $eyes,
        ));

        foreach ($this->filters as $filter) {
            $code = $code->filter($this->filter($filter));
        }

        // Only an uploaded image or one of the logos listed: the value comes from the browser.
        $logo = match (true) {
            $this->logo === self::UPLOADED && $this->upload !== null => Logo::fromBytes($this->upload->get()),
            in_array($this->logo, self::logos(), true) => Logo::fromFile(dirname(__DIR__, 2).'/resources/logos/'.$this->logo),
            default => null,
        };

        return $code
            ->when($this->mirrored)->mirrored()
            ->scanSafety($this->safety())
            ->when($logo !== null)->logo(
                $logo ?? '',
                size: $this->logoAuto ? 'auto' : $this->logoSize,
                padding: $this->logoPadding,
                shape: $this->logoShape,
                plate: $this->logoPlated ? $this->paintOf($this->logoPlate) : null,
                plateRadius: $this->logoPlateRadius,
            );
    }

    /**
     * @param array{outer: string, inner: string, rotate: float, outerPaint: string, innerPaint: string} $settings
     */
    private function corner(EyeBuilder $eye, array $settings): EyeBuilder
    {
        return $eye
            ->when($settings['outer'] !== '')->outer($settings['outer'])
            ->when($settings['inner'] !== '')->inner($settings['inner'])
            ->when((float) $settings['rotate'] !== 0.0)->rotate((float) $settings['rotate'])
            ->when($settings['outerPaint'] !== '')->outerPaint($settings['outerPaint'])
            ->when($settings['innerPaint'] !== '')->innerPaint($settings['innerPaint']);
    }

    /**
     * @param array{name: string, options: array<string, mixed>, roles: list<string>, region: bool, regionX: float, regionY: float, regionRadius: float, corners: list<string>, gradient: array<string, mixed>} $filter
     */
    private function filter(array $filter): NamedFilter
    {
        $options = [];

        foreach ($this->filterFields($filter['name']) as $name => $field) {
            $options[$name] = match ($field['kind']) {
                'gradient' => $this->layerOf($filter['gradient']),
                default => self::cast($filter['options'][$name] ?? $field['value'], $field['kind']),
            };
        }

        $built = Qr::filters()->get($filter['name'], array_filter($options, fn (mixed $value): bool => $value !== null));

        if (! method_exists($built, 'only')) {
            return $built;
        }

        return $built
            ->only(...array_map(Role::from(...), $filter['roles']))
            ->in($filter['region'] ? Region::circle((float) $filter['regionX'], (float) $filter['regionY'], (float) $filter['regionRadius']) : null)
            ->corners(...array_map(Corner::from(...), $filter['corners']));
    }

    /**
     * The options a filter takes, from its constructor: what kind of input each needs, and its default.
     *
     * @return array<string, array{kind: string, value: mixed, required: bool}>
     */
    private function filterFields(string $name): array
    {
        $class = Qr::filters()->classOf($name);
        $constructor = $class === null ? null : new ReflectionClass($class)->getConstructor();
        $fields = [];

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $kind = self::kindOf($parameter);
            $default = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;

            $fields[$parameter->getName()] = ['kind' => $kind, 'required' => ! $parameter->isDefaultValueAvailable(), 'value' => match (true) {
                $default instanceof Property => $default->value,
                $kind === 'list' => implode(', ', (array) ($default ?? match ($parameter->getName()) {
                    'colors' => ['#be123c', '#7c3aed', '#0f766e'],
                    'shapes' => ['dots-large', 'hearts', 'stars'],
                    default => [],
                })),
                $kind === 'color' => is_string($default) ? $default : '',
                default => $default,
            }];
        }

        return $fields;
    }

    private static function kindOf(ReflectionParameter $parameter): string
    {
        $type = $parameter->getType();
        $names = $type instanceof ReflectionNamedType ? [$type->getName()] : array_map(fn ($part): string => (string) $part, method_exists($type, 'getTypes') ? $type->getTypes() : []);

        return match (true) {
            in_array(Property::class, $names, true) => 'property',
            $parameter->getName() === 'gradient' => 'gradient',
            in_array(Paint::class, $names, true) => 'color',
            in_array('bool', $names, true) => 'bool',
            in_array('float', $names, true) => 'float',
            in_array('int', $names, true) => 'int',
            default => 'list',
        };
    }

    private static function cast(mixed $value, string $kind): mixed
    {
        return match ($kind) {
            'float' => (float) $value,
            'int' => (int) $value,
            'bool' => (bool) $value,
            'color' => $value === '' ? null : (string) $value,
            'property' => Property::from((string) $value),
            'list' => self::list((string) $value),
            default => $value,
        };
    }

    /**
     * A comma-separated list: numbers as numbers, the rest as text; empty as none.
     *
     * @return list<float|string>|null
     */
    private static function list(string $value): ?array
    {
        $items = array_values(array_filter(array_map(trim(...), explode(',', $value)), fn (string $item): bool => $item !== ''));

        return $items === [] ? null : array_map(fn (string $item): float|string => is_numeric($item) ? (float) $item : $item, $items);
    }

    /**
     * A shape's options as it has them, by name.
     *
     * @return array<string, mixed>
     */
    private function shapeOptions(string $registry, string $name): array
    {
        try {
            $shape = Qr::{$registry}()->get($name);
        } catch (Throwable) {
            return [];
        }

        return $shape instanceof Configurable
            ? array_map(fn (mixed $value): mixed => $value instanceof BackedEnum ? $value->value : $value, $shape->options())
            : [];
    }

    /**
     * The values an option can take, for the options that are enums, by name.
     *
     * @return array<string, list<string|int>>
     */
    private function choices(string $registry, string $name): array
    {
        try {
            $shape = Qr::{$registry}()->get($name);
        } catch (Throwable) {
            return [];
        }

        $options = $shape instanceof Configurable ? array_filter($shape->options(), fn (mixed $value): bool => $value instanceof BackedEnum) : [];

        return array_map(fn (BackedEnum $value): array => array_column($value::cases(), 'value'), $options);
    }

    /**
     * The options, typed as the shape had them: inputs send everything as text.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function typed(string $registry, string $name, array $options): array
    {
        $defaults = $this->shapeOptions($registry, $name);

        $typed = [];

        foreach ($defaults as $key => $default) {
            $value = $options[$key] ?? $default;
            $typed[$key] = match (true) {
                is_bool($default) => (bool) $value,
                is_int($default) => (int) $value,
                is_float($default) => (float) $value,
                is_array($default) => array_map(floatval(...), (array) $value),
                default => $value,
            };
        }

        return $typed;
    }

    private function safety(): ScanSafety
    {
        if (! $this->guards) {
            return ScanSafety::off();
        }

        $value = fn (string $key): ?float => ($this->limits[$key] ?? '') === '' ? null : (float) $this->limits[$key];

        return new ScanSafety(
            minContrast: $value('minContrast'),
            onViolation: Violation::from($this->violation),
            minMargin: $value('minMargin') === null ? null : (int) $value('minMargin'),
            maxOffset: $value('maxOffset'),
            minScale: $value('minScale'),
            maxSkip: $value('maxSkip'),
            eyeRotationWarning: $value('eyeRotationWarning'),
            maxLogoShare: $value('maxLogoShare'),
            maxScale: $value('maxScale'),
            maxSkew: $value('maxSkew'),
        );
    }

    /**
     * Whether the code reads, as a PNG, with the default reader.
     */
    private function scans(PendingQr $code): string
    {
        try {
            $read = Qr::read($code->png(500));

            return $read->text === $code->text() ? "scans ({$read->decoder})" : 'reads as something else';
        } catch (QrExceptionInterface $exception) {
            return 'does not scan: '.$exception->getMessage();
        }
    }

    /**
     * The logos to choose from, by file name.
     *
     * @return list<string>
     */
    private static function logos(): array
    {
        return array_map(basename(...), glob(dirname(__DIR__, 2).'/resources/logos/*') ?: []);
    }
}
