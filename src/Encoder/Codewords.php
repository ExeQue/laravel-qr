<?php

declare(strict_types=1);

namespace ExeQue\Qr\Encoder;

use ExeQue\Qr\Encoder\Enums\ErrorCorrection;

/**
 * The codeword sequence of a symbol: the data, padded to the version's
 * capacity, in blocks with their error correction, interleaved.
 *
 * @internal
 */
final readonly class Codewords
{
    // Pad codewords, ISO/IEC 18004:2015 section 7.4.10.
    private const array PAD_CODEWORDS = [0b11101100, 0b00010001];

    public function __construct(
        private ReedSolomon $reedSolomon = new ReedSolomon(),
    ) {
    }

    /**
     * The data codewords, split into blocks with their error correction, interleaved.
     *
     * @return list<int>
     */
    public function for(Segment $segment, int $version, ErrorCorrection $errorCorrection): array
    {
        $blocks = $this->blocks($this->data($segment, $version, $errorCorrection), $version, $errorCorrection);
        $ecLength = Tables::ecCodewordsPerBlock($version, $errorCorrection);
        $corrections = array_map(fn (array $block): array => $this->reedSolomon->remainder($block, $ecLength), $blocks);

        return [...$this->interleave($blocks), ...$this->interleave($corrections)];
    }

    /**
     * The segment, a terminator, padding to a whole byte, then pad codewords to fill the capacity.
     *
     * @return list<int>
     */
    public function data(Segment $segment, int $version, ErrorCorrection $errorCorrection): array
    {
        $capacity = Tables::dataCodewords($version, $errorCorrection) * 8;

        $buffer = new BitBuffer();
        $segment->writeTo($buffer, $version);
        $buffer->append(0, min(4, $capacity - $buffer->length()));
        $buffer->append(0, (8 - $buffer->length() % 8) % 8);

        $codewords = $buffer->bytes();

        for ($pad = 0; count($codewords) < $capacity / 8; $pad++) {
            $codewords[] = self::PAD_CODEWORDS[$pad % 2];
        }

        return $codewords;
    }

    /**
     * Splits the data into the version's blocks; where they cannot all be the
     * same length, the later blocks hold one codeword more.
     *
     * @param list<int> $codewords
     *
     * @return non-empty-list<list<int>> Every version has at least one block.
     */
    private function blocks(array $codewords, int $version, ErrorCorrection $errorCorrection): array
    {
        $count = Tables::blocks($version, $errorCorrection);
        $shortLength = intdiv(count($codewords), $count);
        $longBlocks = count($codewords) % $count;

        $blocks = [];
        $offset = 0;

        for ($block = 0; $block < $count; $block++) {
            $length = $shortLength + ($block >= $count - $longBlocks ? 1 : 0);
            $blocks[] = array_slice($codewords, $offset, $length);
            $offset += $length;
        }

        return $blocks;
    }

    /**
     * The first codeword of every block, then the second of every block, and so on.
     *
     * @param non-empty-list<list<int>> $blocks
     *
     * @return list<int>
     */
    private function interleave(array $blocks): array
    {
        $codewords = [];
        $longest = max(array_map(count(...), $blocks));

        for ($index = 0; $index < $longest; $index++) {
            foreach ($blocks as $block) {
                if (isset($block[$index])) {
                    $codewords[] = $block[$index];
                }
            }
        }

        return $codewords;
    }
}
