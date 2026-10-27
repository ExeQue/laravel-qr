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
     * @param int $compressionLevel 0-9, for PNG: how hard zlib packs it; higher is smaller and slower,
     *                              and the image is the same.
     */
    /**
     * The widest image drawn, in pixels: large enough for print, small enough to keep memory in hand.
     */
    public const int MAX_SIZE = 16384;

    public function __construct(
        public int $size = 600,
        public ImageFormat $format = ImageFormat::Png,
        public ?int $dpi = null,
        public int $quality = 90,
        public Metadata $metadata = new Metadata(),
        public int $compressionLevel = 6,
    ) {
        if ($size < 1) {
            throw InvalidWriterOptionException::notPositive('size', $size);
        }

        if ($size > self::MAX_SIZE) {
            throw InvalidWriterOptionException::tooLarge($size, self::MAX_SIZE);
        }

        if ($dpi !== null && $dpi < 1) {
            throw InvalidWriterOptionException::notPositive('dpi', $dpi);
        }

        if ($quality < 1 || $quality > 100) {
            throw InvalidWriterOptionException::qualityOutOfRange($quality);
        }

        if ($compressionLevel < 0 || $compressionLevel > 9) {
            throw InvalidWriterOptionException::compressionLevelOutOfRange($compressionLevel);
        }
    }
}
