<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Raster;

use ExeQue\Qr\Writers\Metadata as Attribution;

/**
 * Writes metadata into PNG and JPEG files, the same way whichever driver drew them: GD cannot, and
 * ImageMagick writes PNG text after the image data and XMP in a form of its own.
 *
 * @internal
 */
final class Metadata
{
    private const string PNG_SIGNATURE = "\x89PNG\r\n\x1A\n";

    // The signature, then the IHDR chunk: its length, type, 13 bytes and CRC.
    private const int PNG_HEADER = 8 + 4 + 4 + 13 + 4;

    /**
     * The fields as plain text chunks, which Windows shows in a file's details, where Latin-1 holds
     * them, international ones otherwise; then XMP; all right after the header, before the image data.
     */
    public static function intoPng(string $png, Attribution $metadata): string
    {
        $chunks = '';

        foreach ($metadata->toTextChunks() as $keyword => $text) {
            $latin = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');

            $chunks .= mb_convert_encoding($latin, 'UTF-8', 'ISO-8859-1') === $text
                ? self::chunk('tEXt', "{$keyword}\0{$latin}")
                : self::international($keyword, $text);
        }

        $chunks .= self::international('XML:com.adobe.xmp', $metadata->toXmp());

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

    /**
     * Keyword, no compression, no language, no translated keyword, the text in UTF-8.
     */
    private static function international(string $keyword, string $text): string
    {
        return self::chunk('iTXt', "{$keyword}\0\0\0\0\0{$text}");
    }

    private static function chunk(string $type, string $data): string
    {
        return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    }
}
