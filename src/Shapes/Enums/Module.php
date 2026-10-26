<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Enums;

use ExeQue\Qr\Factory;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrModule;
use ExeQue\Qr\Shapes\Modules\Brick;
use ExeQue\Qr\Shapes\Modules\Bubbles;
use ExeQue\Qr\Shapes\Modules\Classy;
use ExeQue\Qr\Shapes\Modules\Clover;
use ExeQue\Qr\Shapes\Modules\ConnectedDots;
use ExeQue\Qr\Shapes\Modules\Cross;
use ExeQue\Qr\Shapes\Modules\Diamond;
use ExeQue\Qr\Shapes\Modules\Dot;
use ExeQue\Qr\Shapes\Modules\Drop;
use ExeQue\Qr\Shapes\Modules\Flower;
use ExeQue\Qr\Shapes\Modules\Flowing;
use ExeQue\Qr\Shapes\Modules\Heart;
use ExeQue\Qr\Shapes\Modules\Hexagon;
use ExeQue\Qr\Shapes\Modules\Leaf;
use ExeQue\Qr\Shapes\Modules\Lines;
use ExeQue\Qr\Shapes\Modules\Octagon;
use ExeQue\Qr\Shapes\Modules\Organic;
use ExeQue\Qr\Shapes\Modules\Pill;
use ExeQue\Qr\Shapes\Modules\Rounded;
use ExeQue\Qr\Shapes\Modules\Sketch;
use ExeQue\Qr\Shapes\Modules\Snowflake;
use ExeQue\Qr\Shapes\Modules\Sparkle;
use ExeQue\Qr\Shapes\Modules\Square;
use ExeQue\Qr\Shapes\Modules\Squircle;
use ExeQue\Qr\Shapes\Modules\Star;

/**
 * The built-in module shapes, by their registry names.
 */
enum Module: string implements ProvidesQrModule
{
    case Square = 'square';
    case DotsSmall = 'dots-small';
    case DotsMedium = 'dots-medium';
    case DotsLarge = 'dots-large';
    case FlowingSoft = 'flowing-soft';
    case FlowingMedium = 'flowing-medium';
    case FlowingStrong = 'flowing-strong';
    case Rounded = 'rounded';
    case Squircles = 'squircles';
    case Hearts = 'hearts';
    case Diamonds = 'diamonds';
    case Flowers = 'flowers';
    case Stars = 'stars';
    case Snowflakes = 'snowflakes';
    case Hexagons = 'hexagons';
    case Octagons = 'octagons';
    case Leaves = 'leaves';
    case Drops = 'drops';
    case Sparkles = 'sparkles';
    case Crosses = 'crosses';
    case Clovers = 'clovers';
    case Pills = 'pills';
    case Bricks = 'bricks';
    case Sketch = 'sketch';
    case LinesVertical = 'lines-vertical';
    case LinesHorizontal = 'lines-horizontal';
    case ConnectedDots = 'connected-dots';
    case Classy = 'classy';
    case Bubbles = 'bubbles';
    case Organic = 'organic';

    /**
     * The shape's class, for building it with options.
     *
     * @return class-string<ModuleShape>
     */
    public function class(): string
    {
        return match ($this) {
            self::Square => Square::class,
            self::DotsSmall, self::DotsMedium, self::DotsLarge => Dot::class,
            self::FlowingSoft, self::FlowingMedium, self::FlowingStrong => Flowing::class,
            self::Rounded => Rounded::class,
            self::Squircles => Squircle::class,
            self::Hearts => Heart::class,
            self::Diamonds => Diamond::class,
            self::Flowers => Flower::class,
            self::Stars => Star::class,
            self::Snowflakes => Snowflake::class,
            self::Hexagons => Hexagon::class,
            self::Octagons => Octagon::class,
            self::Leaves => Leaf::class,
            self::Drops => Drop::class,
            self::Sparkles => Sparkle::class,
            self::Crosses => Cross::class,
            self::Clovers => Clover::class,
            self::Pills => Pill::class,
            self::Bricks => Brick::class,
            self::Sketch => Sketch::class,
            self::LinesVertical, self::LinesHorizontal => Lines::class,
            self::ConnectedDots => ConnectedDots::class,
            self::Classy => Classy::class,
            self::Bubbles => Bubbles::class,
            self::Organic => Organic::class,
        };
    }

    /**
     * The options this case sets on its class; the class's defaults for the rest.
     *
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return match ($this) {
            self::DotsSmall => ['diameter' => 0.6],
            self::DotsMedium => ['diameter' => 0.8],
            self::DotsLarge => ['diameter' => 1.0],
            self::FlowingSoft => ['radius' => 0.2],
            self::FlowingMedium => ['radius' => 0.35],
            self::FlowingStrong => ['radius' => 0.5],
            self::LinesHorizontal => ['vertical' => false],
            default => [],
        };
    }

    public function qrModule(): ModuleShape
    {
        return app(Factory::class)->make($this->class(), $this->options());
    }
}
