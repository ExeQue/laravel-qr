<?php

declare(strict_types=1);

namespace ExeQue\Qr\Registries;

use ExeQue\Qr\Decoding\Contracts\Decoder;
use ExeQue\Qr\Decoding\NativeDecoder;
use ExeQue\Qr\Decoding\StackDecoder;
use ExeQue\Qr\Decoding\ZxingDecoder;
use ExeQue\Qr\Registries\Concerns\IsRegistry;

/**
 * Readers by name: the package's own, zxing through Node, the stack that tries them in turn,
 * and an application's own.
 */
final class DecoderRegistry
{
    /** @use IsRegistry<Decoder> */
    use IsRegistry;

    public function kind(): string
    {
        return 'decoder';
    }

    protected function contract(): string
    {
        return Decoder::class;
    }

    protected function provide(object $value): null
    {
        return null;
    }

    protected function builtIn(): array
    {
        return [
            'native' => NativeDecoder::class,
            'zxing' => ZxingDecoder::class,
            'stack' => StackDecoder::class,
        ];
    }

    protected function configKey(): string
    {
        return 'decoders';
    }
}
