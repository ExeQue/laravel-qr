<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters\Enums;

use ExeQue\Qr\Exceptions\InvalidFilterException;
use ExeQue\Qr\Factory;
use ExeQue\Qr\Filters\ByRole;
use ExeQue\Qr\Filters\Compound;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Filters\Contracts\ProvidesQrFilter;
use ExeQue\Qr\Filters\EyeEcho;
use ExeQue\Qr\Filters\EyeFlip;
use ExeQue\Qr\Filters\EyeJitter;
use ExeQue\Qr\Filters\EyeMix;
use ExeQue\Qr\Filters\EyePaint;
use ExeQue\Qr\Filters\EyePalette;
use ExeQue\Qr\Filters\EyeSample;
use ExeQue\Qr\Filters\EyeShade;
use ExeQue\Qr\Filters\EyeSize;
use ExeQue\Qr\Filters\EyeStretch;
use ExeQue\Qr\Filters\EyeTilt;
use ExeQue\Qr\Filters\EyeTwist;
use ExeQue\Qr\Filters\GradientFill;
use ExeQue\Qr\Filters\InRegion;
use ExeQue\Qr\Filters\Jitter;
use ExeQue\Qr\Filters\MixedOffset;
use ExeQue\Qr\Filters\MixedOpacity;
use ExeQue\Qr\Filters\MixedRotation;
use ExeQue\Qr\Filters\MixedShade;
use ExeQue\Qr\Filters\MixedShape;
use ExeQue\Qr\Filters\MixedSize;
use ExeQue\Qr\Filters\MixedSkew;
use ExeQue\Qr\Filters\Palette;
use ExeQue\Qr\Filters\Radial;
use ExeQue\Qr\Filters\Wave;

/**
 * The built-in filters, by their registry names.
 */
enum Filter: string implements ProvidesQrFilter
{
    case MixedRotation = 'mixed-rotation';
    case MixedSize = 'mixed-size';
    case MixedOffset = 'mixed-offset';
    case MixedSkew = 'mixed-skew';
    case MixedOpacity = 'mixed-opacity';
    case MixedShade = 'mixed-shade';
    case MixedShape = 'mixed-shape';
    case Palette = 'palette';
    case Jitter = 'jitter';
    case GradientFill = 'gradient-fill';
    case ByRole = 'by-role';
    case Region = 'region';
    case Wave = 'wave';
    case Radial = 'radial';
    case EyeTilt = 'eye-tilt';
    case EyeSize = 'eye-size';
    case EyePaint = 'eye-paint';
    case EyeMix = 'eye-mix';
    case EyePalette = 'eye-palette';
    case EyeShade = 'eye-shade';
    case EyeJitter = 'eye-jitter';
    case EyeTwist = 'eye-twist';
    case EyeSample = 'eye-sample';
    case EyeEcho = 'eye-echo';
    case EyeStretch = 'eye-stretch';
    case EyeFlip = 'eye-flip';
    case Compound = 'compound';

    /**
     * The filter's class, for building it with options.
     *
     * @return class-string<NamedFilter>
     */
    public function class(): string
    {
        return match ($this) {
            self::MixedRotation => MixedRotation::class,
            self::MixedSize => MixedSize::class,
            self::MixedOffset => MixedOffset::class,
            self::MixedSkew => MixedSkew::class,
            self::MixedOpacity => MixedOpacity::class,
            self::MixedShade => MixedShade::class,
            self::MixedShape => MixedShape::class,
            self::Palette => Palette::class,
            self::Jitter => Jitter::class,
            self::GradientFill => GradientFill::class,
            self::ByRole => ByRole::class,
            self::Region => InRegion::class,
            self::Wave => Wave::class,
            self::Radial => Radial::class,
            self::EyeTilt => EyeTilt::class,
            self::EyeSize => EyeSize::class,
            self::EyePaint => EyePaint::class,
            self::EyeMix => EyeMix::class,
            self::EyePalette => EyePalette::class,
            self::EyeShade => EyeShade::class,
            self::EyeJitter => EyeJitter::class,
            self::EyeTwist => EyeTwist::class,
            self::EyeSample => EyeSample::class,
            self::EyeEcho => EyeEcho::class,
            self::EyeStretch => EyeStretch::class,
            self::EyeFlip => EyeFlip::class,
            self::Compound => Compound::class,
        };
    }

    /**
     * The filter with its default settings. Those without defaults - a shape
     * list, a palette, a gradient, a filter to scope, filters to compound - need options.
     */
    public function qrFilter(): NamedFilter
    {
        if (in_array($this, [self::MixedShape, self::Palette, self::EyePalette, self::GradientFill, self::ByRole, self::Region, self::Compound], true)) {
            throw InvalidFilterException::needsOptions($this->value);
        }

        return app(Factory::class)->make($this->class());
    }
}
