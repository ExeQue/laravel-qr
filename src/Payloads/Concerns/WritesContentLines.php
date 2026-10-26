<?php

declare(strict_types=1);

namespace ExeQue\Qr\Payloads\Concerns;

/**
 * Content lines as vCard (RFC 6350) and iCalendar (RFC 5545) write them: text
 * values escaped, lines folded at 75 octets and ended with CRLF.
 *
 * @internal
 */
trait WritesContentLines
{
    /**
     * Backslashes, semicolons, commas and line breaks escaped.
     */
    private static function escapeText(string $value): string
    {
        return str_replace(["\\", ';', ',', "\r\n", "\n", "\r"], ["\\\\", '\;', '\,', '\n', '\n', '\n'], $value);
    }

    /**
     * Lines folded at 75 octets - a continuation starts with a space - without
     * splitting a multi-byte character, each ended with CRLF.
     *
     * @param list<string> $lines
     */
    private static function contentLines(array $lines): string
    {
        $folded = '';

        foreach ($lines as $line) {
            $limit = 75;

            while (strlen($line) > $limit) {
                $cut = $limit;

                // Back up to the start of a character: UTF-8 continuation bytes are 10xxxxxx.
                while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                    $cut--;
                }

                // Not UTF-8 after all: cut where the line is full.
                $cut = $cut === 0 ? $limit : $cut;

                $folded .= substr($line, 0, $cut)."\r\n ";
                $line = substr($line, $cut);
                $limit = 74;
            }

            $folded .= $line."\r\n";
        }

        return $folded;
    }
}
