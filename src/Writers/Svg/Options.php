<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Svg;

use ExeQue\Qr\Exceptions\InvalidWriterOptionException;
use ExeQue\Qr\Writers\Metadata;
use ExeQue\Qr\Writers\Svg\Enums\ColorNotation;

final readonly class Options
{
    /**
     * @param int|null $size Width and height in pixels; none when null, so CSS sizes the code.
     * @param bool $pixels Coordinates in pixels rather than modules; needs a size.
     * @param int $precision Decimals in numbers.
     * @param bool $currentColor Write the code's own paint as `currentColor`, for CSS to color.
     * @param string|null $symbol Wrap the code in a `<symbol>` with this id, to `<use>` it many times on a page.
     * @param bool $compact Path data at its smallest; false writes it spaced out and absolute, for reading.
     * @param bool $inlineSvgLogos Inline an SVG logo as SVG; false embeds it as an image, kept apart from the page.
     */
    public function __construct(
        public ?int $size = null,
        public bool $pixels = false,
        public int $precision = 3,
        public bool $xmlDeclaration = false,
        public bool $currentColor = false,
        public ?string $symbol = null,
        public ColorNotation $colorNotation = ColorNotation::Hex,
        public Metadata $metadata = new Metadata(),
        public bool $compact = true,
        public bool $inlineSvgLogos = true,
    ) {
        if ($pixels && $size === null) {
            throw InvalidWriterOptionException::pixelsWithoutSize();
        }

        if ($symbol !== null && preg_match('/^[A-Za-z][\w.-]*$/', $symbol) !== 1) {
            throw InvalidWriterOptionException::invalidSymbolId($symbol);
        }
    }
}
