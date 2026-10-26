<?php

declare(strict_types=1);

namespace ExeQue\Qr\Decoding;

use ExeQue\Qr\Decoding\Contracts\Decoder;
use ExeQue\Qr\Decoding\Native\Bitmap;
use ExeQue\Qr\Decoding\Native\FinderLocator;
use ExeQue\Qr\Decoding\Native\Sampler;
use ExeQue\Qr\Decoding\Native\SymbolReader;
use ExeQue\Qr\Exceptions\UnreadableCodeException;

/**
 * The package's own reader, in PHP: finds the finder patterns, reads the grid of modules
 * under the perspective they give, and decodes it with error correction. It reads raster
 * images - PNG, JPEG, WebP, GIF - through Imagick, light codes on dark ones too, and needs
 * no Node. For photos of large codes at a steep angle, zxing reads more.
 */
final readonly class NativeDecoder implements Decoder
{
    public function __construct(
        private SymbolReader $reader = new SymbolReader(),
    ) {
    }

    public function decode(string $image): Decoded
    {
        $bitmap = Bitmap::fromImage($image);
        $failure = null;

        // The cheap global threshold first; then one that follows uneven light; each the other way round too.
        foreach ([fn (): array => $bitmap->global(), fn (): array => $bitmap->local()] as $threshold) {
            $dark = $threshold();

            foreach ([false, true] as $inverted) {
                try {
                    return $this->read($inverted ? array_map(fn (bool $pixel): bool => ! $pixel, $dark) : $dark, $bitmap);
                } catch (UnreadableCodeException $exception) {
                    // A code found but not read says more than one not found at all.
                    $failure = $failure === null || $failure->getMessage() === UnreadableCodeException::noCode()->getMessage() ? $exception : $failure;
                }
            }
        }

        throw $failure;
    }

    /**
     * @param list<bool> $dark
     */
    private function read(array $dark, Bitmap $bitmap): Decoded
    {
        [$topLeft, $topRight, $bottomLeft, $module] = new FinderLocator()->locate($dark, $bitmap->width, $bitmap->height);
        $sampler = new Sampler();
        $modules = $sampler->sample($dark, $bitmap->width, $bitmap->height, $topLeft, $topRight, $bottomLeft, $module);

        // Measuring a large symbol can be a few modules off; its version information says for sure.
        $version = SymbolReader::versionInformation($modules);

        if ($version !== null && 17 + 4 * $version !== count($modules)) {
            $modules = $sampler->sample($dark, $bitmap->width, $bitmap->height, $topLeft, $topRight, $bottomLeft, $module, 17 + 4 * $version);
        }

        try {
            return $this->reader->read($modules);
        } catch (UnreadableCodeException $exception) {
            // A mirrored symbol reads right turned over its diagonal.
            try {
                return $this->reader->read(array_map(null, ...$modules));
            } catch (UnreadableCodeException) {
                throw $exception;
            }
        }
    }
}
