<?php

declare(strict_types=1);

namespace ExeQue\Qr\Encoder;

use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Encoder\Enums\Mask;
use ExeQue\Qr\Exceptions\ContentTooLongException;
use ExeQue\Qr\Exceptions\InvalidVersionException;

/**
 * Turns content into a QR code symbol, as ISO/IEC 18004:2015 describes.
 */
final readonly class Encoder
{
    private const int MAX_VERSION = 40;

    public function __construct(
        private Codewords $codewords = new Codewords(),
    ) {
    }

    /**
     * @param int $minVersion The smallest version to use, so a design can keep one size.
     * @param Mask|null $mask Null picks the mask with the lowest penalty.
     */
    public function encode(
        string $content,
        ErrorCorrection $errorCorrection = ErrorCorrection::Medium,
        int $minVersion = 1,
        ?Mask $mask = null,
    ): Matrix {
        if ($minVersion < 1 || $minVersion > self::MAX_VERSION) {
            throw InvalidVersionException::outOfRange($minVersion);
        }

        $segment = Segment::for($content);
        $version = $this->smallestVersion($segment, $errorCorrection, $minVersion);

        $grid = new Grid($version);
        $grid->place($this->codewords->for($segment, $version, $errorCorrection));

        $mask ??= $this->bestMask($grid, $errorCorrection);

        return $grid->masked($mask, $errorCorrection)->toMatrix($errorCorrection, $mask);
    }

    /**
     * @param int<1, 40> $minVersion
     *
     * @return int<1, 40>
     */
    private function smallestVersion(Segment $segment, ErrorCorrection $errorCorrection, int $minVersion): int
    {
        for ($version = $minVersion; $version <= self::MAX_VERSION; $version++) {
            if ($segment->bitLength($version) <= Tables::dataCodewords($version, $errorCorrection) * 8) {
                return $version;
            }
        }

        throw ContentTooLongException::for(
            $segment->bitLength(self::MAX_VERSION),
            Tables::dataCodewords(self::MAX_VERSION, $errorCorrection) * 8,
            $errorCorrection,
        );
    }

    private function bestMask(Grid $grid, ErrorCorrection $errorCorrection): Mask
    {
        $penalties = [];

        foreach (Mask::cases() as $mask) {
            $penalties[$mask->value] = $grid->masked($mask, $errorCorrection)->penalty();
        }

        // On a tie the lowest pattern wins: the first key with the lowest penalty.
        return Mask::from(array_keys($penalties, min($penalties), true)[0]);
    }
}
