<?php

declare(strict_types=1);

namespace ExeQue\Qr\Encoder;

/**
 * @internal
 */
final class BitBuffer
{
    /**
     * @var list<int>
     */
    private array $bits = [];

    /**
     * Appends the low `$length` bits of `$value`, most significant first.
     */
    public function append(int $value, int $length): void
    {
        for ($bit = $length - 1; $bit >= 0; $bit--) {
            $this->bits[] = ($value >> $bit) & 1;
        }
    }

    public function length(): int
    {
        return count($this->bits);
    }

    /**
     * The bits as bytes; the length must be a whole number of bytes.
     *
     * @return list<int>
     */
    public function bytes(): array
    {
        $bytes = [];

        foreach (array_chunk($this->bits, 8) as $chunk) {
            $bytes[] = array_reduce($chunk, fn (int $byte, int $bit): int => ($byte << 1) | $bit, 0);
        }

        return $bytes;
    }
}
