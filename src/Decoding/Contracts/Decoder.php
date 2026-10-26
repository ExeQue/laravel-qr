<?php

declare(strict_types=1);

namespace ExeQue\Qr\Decoding\Contracts;

use ExeQue\Qr\Decoding\Decoded;
use ExeQue\Qr\Exceptions\UnreadableCodeException;

/**
 * Reads a QR code from an image.
 */
interface Decoder
{
    /**
     * @param string $image The image's bytes: PNG, JPEG, WebP or GIF; SVG where the decoder says so.
     *
     * @throws UnreadableCodeException When there is no code to be read.
     */
    public function decode(string $image): Decoded;
}
