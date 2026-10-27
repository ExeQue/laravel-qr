<?php

declare(strict_types=1);

namespace ExeQue\Qr\Builders;

use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Exceptions\InvalidLogoException;
use ExeQue\Qr\Exceptions\InvalidOptionException;
use ExeQue\Qr\Logos\Contracts\ProvidesQrLogo;
use ExeQue\Qr\Logos\Enums\LogoShape;
use ExeQue\Qr\Logos\Logo;
use ExeQue\Qr\Logos\LogoStyle;
use ExeQue\Qr\Support\Hydrate;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use SplFileInfo;

/**
 * A logo and where and how it sits. Each call returns a changed copy.
 */
final class LogoBuilder
{
    use Conditionable;
    use Macroable;
    use Tappable;

    private ?Logo $logo = null;

    /**
     * @var array<array-key, mixed>
     */
    private array $settings = [];

    /**
     * The image: a Logo or something providing one, a file (an uploaded one too), a data: URI, an
     * SVG or raw image bytes - or a disk (by name or instance) and a path on it. A string is the image
     * itself, never a path: a file is given as a file, or by path through file().
     */
    public function from(Logo|ProvidesQrLogo|SplFileInfo|Filesystem|string|null $source, ?string $path = null): self
    {
        $logo = match (true) {
            $path !== null => Logo::fromBytes((string) self::disk($source)->get($path)),
            $source instanceof Logo => $source,
            $source instanceof ProvidesQrLogo => $source->qrLogo(),
            $source instanceof SplFileInfo => Logo::fromFile($source->getPathname()),
            is_string($source) && str_starts_with($source, 'data:') => Logo::fromDataUri($source),
            // A short line of text is no image; most likely a path or URL meant as one.
            is_string($source) && strlen($source) < 4096 && preg_match('/[<\x00-\x08\x0E-\x1F\x7F-\xFF]/', $source) !== 1 => throw InvalidLogoException::notAnImage($source),
            is_string($source) => Logo::fromBytes($source),
            default => throw InvalidLogoException::unreadableSource(get_debug_type($source)),
        };

        return clone($this, ['logo' => $logo]);
    }

    /**
     * The logo's width as a share of the symbol's, or 'auto' for as large as the error correction
     * can make up for.
     */
    public function size(float|string $size): self
    {
        return $this->with(size: $size);
    }

    /**
     * Modules of clear space around the logo.
     */
    public function padding(int $padding): self
    {
        return $this->with(padding: $padding);
    }

    public function shape(LogoShape|string $shape): self
    {
        return $this->with(shape: $shape instanceof LogoShape ? $shape : self::logoShape($shape));
    }

    /**
     * A backing behind the logo and its padding, in any color form, with a corner radius in modules.
     *
     * @param Paint|string|array<string, mixed> $paint
     */
    public function plate(Paint|string|array $paint, float $radius = 0.0): self
    {
        return $this->with(plate: Hydrate::paint($paint), plateRadius: $radius);
    }

    /**
     * The logo's top-left corner, in modules from the symbol's; it is centered otherwise.
     */
    public function position(float $x, float $y): self
    {
        return $this->with(position: [$x, $y]);
    }

    /**
     * Settings by name, as `->logo($source, size: 0.2)` takes them.
     */
    /**
     * The image from a file on this server, by its path.
     */
    public function file(string $path): self
    {
        return clone($this, ['logo' => Logo::fromFile($path)]);
    }

    public function with(mixed ...$settings): self
    {
        if (isset($settings['file'])) {
            $builder = $this->file((string) $settings['file']);
            unset($settings['file']);

            return $builder->with(...$settings);
        }

        if (isset($settings['shape']) && is_string($settings['shape'])) {
            $settings['shape'] = self::logoShape($settings['shape']);
        }

        if (($settings['size'] ?? null) === 'auto') {
            $settings['size'] = null;
        }

        if (isset($settings['plate'])) {
            $settings['plate'] = Hydrate::paint($settings['plate']);
        }

        return clone($this, ['settings' => [...$this->settings, ...$settings]]);
    }

    public function toLogoStyle(): LogoStyle
    {
        return new LogoStyle($this->logo ?? throw InvalidLogoException::noSource(), ...$this->settings);
    }

    private static function disk(Logo|ProvidesQrLogo|SplFileInfo|Filesystem|string|null $disk): Filesystem
    {
        return $disk instanceof Filesystem ? $disk : Storage::disk(is_string($disk) ? $disk : null);
    }

    private static function logoShape(LogoShape|string $shape): LogoShape
    {
        return $shape instanceof LogoShape ? $shape : (LogoShape::tryFrom($shape)
            ?? throw InvalidOptionException::notOneOf(LogoShape::class, 'shape', array_column(LogoShape::cases(), 'value'), $shape));
    }
}
