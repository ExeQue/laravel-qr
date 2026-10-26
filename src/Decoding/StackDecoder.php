<?php

declare(strict_types=1);

namespace ExeQue\Qr\Decoding;

use ExeQue\Qr\Decoding\Contracts\Decoder;
use ExeQue\Qr\Exceptions\QrExceptionInterface;
use ExeQue\Qr\Exceptions\UnreadableCodeException;
use ExeQue\Qr\Registries\DecoderRegistry;

/**
 * Tries decoders in turn until one reads the code: one that cannot read it, or is not
 * installed, passes it on to the next.
 */
final readonly class StackDecoder implements Decoder
{
    /**
     * @param list<string> $decoders Registered decoder names, in the order to try them.
     */
    public function __construct(
        private DecoderRegistry $registry,
        private array $decoders = ['zxing', 'native'],
    ) {
    }

    public function decode(string $image): Decoded
    {
        $reasons = [];

        foreach ($this->decoders as $name) {
            try {
                return $this->registry->get($name)->decode($image)->withDecoder($name);
            } catch (QrExceptionInterface $exception) {
                $reasons[] = "{$name}: {$exception->getMessage()}";
            }
        }

        throw UnreadableCodeException::noDecoderRead($reasons);
    }
}
