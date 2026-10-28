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
        $segment = self::exif($metadata)."\xFF\xE1".pack('n', strlen($data) + 2).$data;

        // After the start of image, and the JFIF segment when there is one.
        $at = substr($jpeg, 2, 2) === "\xFF\xE0" ? 4 + (unpack('n', substr($jpeg, 4, 2)) ?: [1 => 0])[1] : 2;

        return substr($jpeg, 0, $at).$segment.substr($jpeg, $at);
    }

    /**
     * The fields as EXIF, in an APP1 segment: what Windows shows in a JPEG's details. Text in the
     * Windows tags is UTF-16; in the standard ones, UTF-8.
     */
    private static function exif(Attribution $metadata): string
    {
        $utf16 = fn (string $text): array => [1, mb_convert_encoding($text, 'UTF-16LE', 'UTF-8')."\0\0"];
        $ascii = fn (string $text): array => [2, $text."\0"];
        $keywords = $metadata->keywords === [] ? null : implode('; ', $metadata->keywords);

        // Tag => [type, bytes]: 1 is a byte string, 2 text; tags in ascending order, as EXIF wants.
        $tags = array_filter([
            0x010E => $metadata->title === null ? null : $ascii($metadata->title),
            0x013B => $metadata->author === null ? null : $ascii($metadata->author),
            0x8298 => $metadata->copyright === null ? null : $ascii($metadata->copyright),
            0x9C9B => $metadata->title === null ? null : $utf16($metadata->title),
            0x9C9C => $metadata->description === null ? null : $utf16($metadata->description),
            0x9C9D => $metadata->author === null ? null : $utf16($metadata->author),
            0x9C9E => $keywords === null ? null : $utf16($keywords),
        ]);

        if ($tags === []) {
            return '';
        }

        // A little-endian TIFF header, one directory of the tags, and the values too long to sit in it after.
        $offset = 8 + 2 + 12 * count($tags) + 4;
        [$entries, $values] = ['', ''];

        foreach ($tags as $tag => [$type, $bytes]) {
            $inline = strlen($bytes) <= 4;
            $entries .= pack('vvV', $tag, $type, strlen($bytes)).($inline ? str_pad($bytes, 4, "\0") : pack('V', $offset + strlen($values)));
            $values .= $inline ? '' : $bytes.(strlen($bytes) % 2 === 1 ? "\0" : '');
        }

        $data = "Exif\0\0II*\0".pack('V', 8).pack('v', count($tags)).$entries.pack('V', 0).$values;

        return "\xFF\xE1".pack('n', strlen($data) + 2).$data;
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
