<?php

declare(strict_types=1);

namespace ExeQue\Qr\Decoding;

use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Encoder\Enums\Mask;
use Stringable;

/**
 * What a code holds, and what is known about the symbol it was read from.
 */
final readonly class Decoded implements Stringable
{
    /**
     * @param int|null $version The symbol's version, 1-40, when the decoder tells.
     * @param string|null $decoder The name of the decoder that read it.
     * @param string|null $bytes The content as the code holds it, when the decoder tells: binary content
     *                           byte for byte, where `$text` reads bytes without a character set as Latin-1.
     */
    public function __construct(
        public string $text,
        public ?int $version = null,
        public ?ErrorCorrection $errorCorrection = null,
        public ?Mask $mask = null,
        public ?string $decoder = null,
        public ?string $bytes = null,
    ) {
    }

    public function withDecoder(string $decoder): self
    {
        return clone($this, ['decoder' => $decoder]);
    }

    public function __toString(): string
    {
        return $this->text;
    }
}
