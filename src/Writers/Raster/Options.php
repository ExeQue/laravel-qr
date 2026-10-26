<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Raster;

use ExeQue\Qr\Exceptions\InvalidWriterOptionException;
use ExeQue\Qr\Writers\Metadata;
use ExeQue\Qr\Writers\Raster\Enums\ImageFormat;

final readonly class Options
{
    /**
     * @param int $size Width and height in pixels.
     * @param int|null $dpi Resolution written into PNG and JPEG files, for print; WebP has no place for it.
     * @param int $quality 1-100, for JPEG and WebP.
     */
    public function __construct(
        public int $size = 600,
        public ImageFormat $format = ImageFormat::Png,
        public ?int $dpi = null,
        public int $quality = 90,
        public Metadata $metadata = new Metadata(),
    ) {
        if ($size < 1) {
            throw InvalidWriterOptionException::notPositive('size', $size);
        }

        if ($dpi !== null && $dpi < 1) {
            throw InvalidWriterOptionException::notPositive('dpi', $dpi);
        }

        if ($quality < 1 || $quality > 100) {
            throw InvalidWriterOptionException::qualityOutOfRange($quality);
        }
    }
}
