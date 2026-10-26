<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Raster\Gd;

use ExeQue\Qr\Writers\Metadata as Attribution;

/**
 * Writes metadata into the files GD made, which GD itself cannot.
 *
 * @internal
 */
final class Metadata
{
    private const string PNG_SIGNATURE = "\x89PNG\r\n\x1A\n";

    // The signature, then the IHDR chunk: its length, type, 13 bytes and CRC.
    private const int PNG_HEADER = 8 + 4 + 4 + 13 + 4;

    /**
     * The text chunks and XMP as international text chunks, right after the header.
     */
    public static function intoPng(string $png, Attribution $metadata): string
    {
        $chunks = '';

        foreach ([...$metadata->toTextChunks(), 'XML:com.adobe.xmp' => $metadata->toXmp()] as $keyword => $text) {
            // Keyword, no compression, no language, no translated keyword, the text in UTF-8.
            $chunks .= self::chunk('iTXt', "{$keyword}\0\0\0\0\0{$text}");
        }

        return str_starts_with($png, self::PNG_SIGNATURE)
            ? substr($png, 0, self::PNG_HEADER).$chunks.substr($png, self::PNG_HEADER)
            : $png;
    }

    /**
     * XMP in an APP1 segment, after the JFIF one.
     */
    public static function intoJpeg(string $jpeg, Attribution $metadata): string
    {
        $data = "http://ns.adobe.com/xap/1.0/\0".$metadata->toXmp();
        $segment = "\xFF\xE1".pack('n', strlen($data) + 2).$data;

        // After the start of image, and the JFIF segment when there is one.
        $at = substr($jpeg, 2, 2) === "\xFF\xE0" ? 4 + (unpack('n', substr($jpeg, 4, 2)) ?: [1 => 0])[1] : 2;

        return substr($jpeg, 0, $at).$segment.substr($jpeg, $at);
    }

    private static function chunk(string $type, string $data): string
    {
        return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    }
}
