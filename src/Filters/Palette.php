<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Colors\Color;
use ExeQue\Qr\Contracts\Color as ColorContract;
use ExeQue\Qr\Exceptions\InvalidFilterException;
use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Support\Normalizer;
use ExeQue\Qr\Support\Seed;
use ExeQue\Qr\Validation\Contracts\HasOptionRules;
use ExeQue\Qr\Validation\PaintRule;
use ExeQue\Qr\Validation\Rules\Options;

/**
 * Paints each module a color of its own from a list, optionally weighted: confetti codes.
 */
final class Palette implements ModuleFilter, NamedFilter, HasOptionRules
{
    use Scoped;

    /**
     * @var non-empty-list<ColorContract>
     */
    private array $colors;

    /**
     * @param list<ColorContract|string|array<string, mixed>> $colors
     * @param list<float>|null $weights One per color; equal when null.
     */
    public function __construct(
        array $colors,
        private ?array $weights = null,
        private int $seed = 0,
    ) {
        // As floats whatever they came as (form strings, whole numbers from JSON), so equal filters are equal.
        $this->weights = $weights === null ? null : array_map(floatval(...), $weights);

        if ($colors === [] || ($weights !== null && count($weights) !== count($colors))) {
            throw InvalidFilterException::weightsDoNotMatch($this->name(), count($colors), $weights === null ? null : count($weights));
        }

        $this->colors = array_map(Color::make(...), $colors);
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        if (! $this->appliesTo($cell)) {
            return $style;
        }

        return $style->withPaint(Seed::pick($cell->seed, $this->colors, $this->weights, "{$this->name()}:{$this->seed}"));
    }

    public static function optionRules(): array
    {
        return [
            'colors' => ['required', 'list', 'min:1', 'max:16'],
            'colors.*' => [PaintRule::colors()],
            'weights' => ['sometimes', 'nullable', 'list', 'max:16'],
            'weights.*' => ['numeric', 'min:0'],
            'seed' => Options::integer(),
        ];
    }

    public function name(): string
    {
        return 'palette';
    }

    public function options(): array
    {
        return [...array_filter(['colors' => Normalizer::value($this->colors), 'weights' => $this->weights, 'seed' => $this->seed], fn (mixed $value): bool => $value !== null), ...$this->scope()];
    }
}
