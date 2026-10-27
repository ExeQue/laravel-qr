<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering;

use ExeQue\Qr\Builders\PendingStyle;
use ExeQue\Qr\Colors\Contrast;
use ExeQue\Qr\Colors\Hex;
use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Exceptions\InvalidStyleException;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Logos\LogoStyle;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Shapes\Contracts\Directional;
use ExeQue\Qr\Shapes\Contracts\Symmetric;
use ExeQue\Qr\Shapes\Enums\Eye;
use ExeQue\Qr\Shapes\Enums\Module;
use ExeQue\Qr\Support\Paints;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Everything about how a code looks. Every `with*()` returns a changed copy.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class Style implements Arrayable, JsonSerializable
{
    /**
     * @param array<value-of<Corner>, EyeStyle> $eyes
     * @param Paint|null $background Null for none.
     * @param int $margin The quiet zone, in modules.
     * @param list<(ModuleFilter&NamedFilter)|(EyeFilter&NamedFilter)> $filters In the order they run.
     */
    private function __construct(
        public ModuleStyle $modules,
        private array $eyes,
        public ?Paint $background,
        public int $margin,
        private array $filters = [],
        public ?LogoStyle $logo = null,
    ) {
        self::checkMargin($margin);
    }

    /**
     * A style described with the style builder's methods and built when it is first used, as
     * a named style in config/qr.php: Style::make()->modules('dots-medium')->paint('#1e1b4b').
     */
    public static function make(): PendingStyle
    {
        return PendingStyle::make();
    }

    /**
     * Black squares on white, with a four-module quiet zone.
     */
    public static function default(): self
    {
        $eye = EyeStyle::of(Eye::Square->qrOuterEye(), Eye::Square->qrInnerEye());

        return new self(
            new ModuleStyle(Module::Square->qrModule(), new Hex('#000000')),
            array_fill_keys(array_column(Corner::cases(), 'value'), $eye),
            new Hex('#ffffff'),
            4,
        );
    }

    /**
     * A style made again from its plain values.
     *
     * @param array<string, mixed> $data As `toArray()` gives it.
     */
    public static function fromArray(array $data): self
    {
        return app(StyleSerializer::class)->hydrate($data);
    }

    /**
     * The style as plain values, settings at their defaults left out: what it is
     * cached, compared and stored by.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return app(StyleSerializer::class)->dehydrate($this);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * This style with `$over` on top: what `$over` sets wins, part by part.
     * Each eye's outer and inner part merge on their own, filters are added after
     * these ones, and a logo replaces this one's.
     */
    /**
     * `$over` laid over this style: what it changes from the defaults, or from `$from`, the style it was
     * built from, so a value it set back to the package's default still counts.
     */
    public function merge(self $over, ?self $from = null): self
    {
        return app(StyleSerializer::class)->merge($this, $over, $from);
    }

    public function equals(self $other): bool
    {
        return $this->toArray() === $other->toArray();
    }

    public function eye(Corner $corner): EyeStyle
    {
        return $this->eyes[$corner->value];
    }

    public function withModules(ModuleStyle $modules): self
    {
        return clone($this, ['modules' => $modules]);
    }

    /**
     * The paint of the modules, and of every eye without one of its own.
     */
    public function withPaint(Paint $paint): self
    {
        return $this->withModules($this->modules->withPaint($paint));
    }

    public function withEye(Corner $corner, EyeStyle $eye): self
    {
        return clone($this, ['eyes' => [...$this->eyes, $corner->value => $eye]]);
    }

    public function withEyes(EyeStyle $eye): self
    {
        return clone($this, ['eyes' => array_fill_keys(array_keys($this->eyes), $eye)]);
    }

    /**
     * Turns the top-right eye a quarter clockwise and the bottom-left one a
     * quarter back, on top of any turn they have, so a corner drawn top-left on
     * the shape points outward on every eye.
     */
    public function mirrored(): self
    {
        $style = $this;

        foreach ([Corner::TopRight, Corner::BottomLeft] as $corner) {
            $eye = $style->eye($corner);
            $style = $style->withEye($corner, $eye->withOuter(self::turned($eye->outer, $corner))->withInner(self::turned($eye->inner, $corner)));
        }

        return $style;
    }

    /**
     * A part turned as it sits on the corner, unless it turns itself (a directional part) or is its
     * own mirror image (a symmetric one).
     */
    private static function turned(EyePartStyle $part, Corner $corner): EyePartStyle
    {
        return $part->shape instanceof Symmetric || ($part->shape instanceof Directional && $part->shape->isDirectional()) ? $part : $part->rotateBy($corner->turn());
    }

    public function withBackground(?Paint $background): self
    {
        return clone($this, ['background' => $background]);
    }

    public function withMargin(int $margin): self
    {
        self::checkMargin($margin);

        return clone($this, ['margin' => $margin]);
    }

    public function withLogo(?LogoStyle $logo): self
    {
        return clone($this, ['logo' => $logo]);
    }

    /**
     * Adds a filter after the others.
     */
    public function withFilter((ModuleFilter&NamedFilter)|(EyeFilter&NamedFilter) $filter): self
    {
        return clone($this, ['filters' => [...$this->filters, $filter]]);
    }

    /**
     * Replaces the filters.
     */
    public function withFilters((ModuleFilter&NamedFilter)|(EyeFilter&NamedFilter) ...$filters): self
    {
        return clone($this, ['filters' => array_values($filters)]);
    }

    /**
     * Drops every filter with the name.
     */
    public function withoutFilter(string $name): self
    {
        return clone($this, ['filters' => array_values(array_filter(
            $this->filters,
            fn (NamedFilter $filter): bool => $filter->name() !== $name,
        ))]);
    }

    /**
     * @return list<(ModuleFilter&NamedFilter)|(EyeFilter&NamedFilter)>
     */
    public function filters(): array
    {
        return $this->filters;
    }

    private static function checkMargin(int $margin): void
    {
        if ($margin < 0) {
            throw InvalidStyleException::negativeMargin($margin);
        }
    }

    /**
     * The worst WCAG contrast between a paint of the modules or eyes and the
     * background (white when there is none), before any filter.
     */
    public function contrast(): float
    {
        $grounds = $this->background === null ? [new Hex('#ffffff')] : Paints::colors($this->background);
        $paints = [$this->modules->paint];

        foreach ($this->eyes as $eye) {
            array_push($paints, ...array_filter([$eye->outer->paint, $eye->inner->paint]));
        }

        $worst = INF;

        foreach ($paints as $paint) {
            foreach (Paints::colors($paint) as $color) {
                foreach ($grounds as $ground) {
                    $worst = min($worst, Contrast::ratio($color, $ground, $this->modules->opacity));
                }
            }
        }

        return $worst;
    }
}
