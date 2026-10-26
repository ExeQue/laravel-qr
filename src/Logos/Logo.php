<?php

declare(strict_types=1);

namespace ExeQue\Qr\Logos;

use ExeQue\Qr\Exceptions\InvalidLogoException;
use ExeQue\Qr\Logos\Contracts\SvgSanitizer;
use Imagick;

/**
 * An image to place over a code: PNG, JPEG, WebP, GIF (its first frame) or SVG.
 * An SVG is sanitized when the logo is made, by the sanitizer given or the one
 * bound in the container, so what it holds is always safe to inline.
 */
final readonly class Logo
{
    /**
     * @param string $hash Of the contents: what caching goes by.
     */
    private function __construct(
        public string $contents,
        public string $mimeType,
        public float $width,
        public float $height,
        public string $hash,
    ) {
    }

    /**
     * An image of any supported type, told apart by its first bytes.
     */
    public static function fromBytes(string $bytes, ?SvgSanitizer $sanitizer = null): self
    {
        if (preg_match('/^\s*(<\?xml[^>]*>\s*)?(<!--.*?-->\s*)*<svg[\s>]/s', $bytes) === 1) {
            return self::fromSvg($bytes, $sanitizer);
        }

        $mimeType = match (true) {
            str_starts_with($bytes, "\x89PNG\r\n\x1A\n") => 'image/png',
            str_starts_with($bytes, "\xFF\xD8\xFF") => 'image/jpeg',
            str_starts_with($bytes, 'GIF8') => 'image/gif',
            str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP' => 'image/webp',
            default => throw InvalidLogoException::unsupportedType(),
        };

        if ($mimeType === 'image/gif') {
            [$bytes, $mimeType] = [self::firstFrame($bytes), 'image/png'];
        }

        $size = getimagesizefromstring($bytes) ?: throw InvalidLogoException::unreadable();

        return new self($bytes, $mimeType, (float) $size[0], (float) $size[1], hash('xxh128', $bytes));
    }

    public static function fromFile(string $path, ?SvgSanitizer $sanitizer = null): self
    {
        $bytes = is_file($path) && is_readable($path) ? file_get_contents($path) : false;

        return self::fromBytes($bytes === false ? throw InvalidLogoException::missingFile($path) : $bytes, $sanitizer);
    }

    /**
     * A `data:` URI of an image, base64 or not.
     */
    public static function fromDataUri(string $uri, ?SvgSanitizer $sanitizer = null): self
    {
        if (preg_match('/^data:image\/[\w.+-]+(;[\w=-]+)*?(;base64)?,(.*)$/s', $uri, $match) !== 1) {
            throw InvalidLogoException::notADataUri();
        }

        $bytes = $match[2] === ';base64' ? base64_decode($match[3], true) : rawurldecode($match[3]);

        return self::fromBytes($bytes === false ? throw InvalidLogoException::notADataUri() : $bytes, $sanitizer);
    }

    public static function fromSvg(string $svg, ?SvgSanitizer $sanitizer = null): self
    {
        $clean = ($sanitizer ?? app(SvgSanitizer::class))->clean($svg);
        [$width, $height] = self::svgSize($clean);

        return new self($clean, 'image/svg+xml', $width, $height, hash('xxh128', $clean));
    }

    public function isSvg(): bool
    {
        return $this->mimeType === 'image/svg+xml';
    }

    /**
     * Width over height.
     */
    public function aspectRatio(): float
    {
        return $this->width / $this->height;
    }

    public function toDataUri(): string
    {
        return "data:{$this->mimeType};base64,".base64_encode($this->contents);
    }

    /**
     * The size an SVG says it has: its viewBox, else its width and height, else a square.
     *
     * @return array{float, float}
     */
    private static function svgSize(string $svg): array
    {
        if (preg_match('/<svg[^>]*\sviewBox="\s*[-\d.]+[\s,]+[-\d.]+[\s,]+([\d.]+)[\s,]+([\d.]+)\s*"/i', $svg, $match) === 1 && (float) $match[1] > 0 && (float) $match[2] > 0) {
            return [(float) $match[1], (float) $match[2]];
        }

        $width = preg_match('/<svg[^>]*\swidth="([\d.]+)/i', $svg, $w) === 1 ? (float) $w[1] : 0.0;
        $height = preg_match('/<svg[^>]*\sheight="([\d.]+)/i', $svg, $h) === 1 ? (float) $h[1] : 0.0;

        return $width > 0 && $height > 0 ? [$width, $height] : [1.0, 1.0];
    }

    /**
     * A GIF's first frame, as PNG.
     */
    private static function firstFrame(string $gif): string
    {
        if (! extension_loaded('imagick')) {
            throw InvalidLogoException::gifNeedsImagick(); // @codeCoverageIgnore
        }

        $image = new Imagick();
        $image->readImageBlob($gif);
        $image->setIteratorIndex(0);

        $frame = $image->getImage();
        $frame->setImageFormat('png');
        $frame->setOption('png:exclude-chunks', 'date,time');

        return $frame->getImageBlob();
    }
}
