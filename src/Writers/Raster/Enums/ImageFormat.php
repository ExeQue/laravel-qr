<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Raster\Enums;

enum ImageFormat: string
{
    case Png = 'png';
    case Jpeg = 'jpeg';
    case Webp = 'webp';

    public function mimeType(): string
    {
        return "image/{$this->value}";
    }

    public function extension(): string
    {
        return $this === self::Jpeg ? 'jpg' : $this->value;
    }

    /**
     * JPEG has no transparency: it is flattened onto the background, or white.
     */
    public function hasTransparency(): bool
    {
        return $this !== self::Jpeg;
    }
}
