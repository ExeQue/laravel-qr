<?php

declare(strict_types=1);

namespace ExeQue\Qr\Logos\Sanitizers;

use enshrined\svgSanitize\Sanitizer;
use ExeQue\Qr\Exceptions\InvalidLogoException;
use ExeQue\Qr\Logos\Contracts\SvgSanitizer;

/**
 * Sanitizes with enshrined/svg-sanitize, a dedicated and widely used
 * sanitizer, with remote references removed.
 */
final readonly class EnshrinedSvgSanitizer implements SvgSanitizer
{
    public function clean(string $svg): string
    {
        $sanitizer = new Sanitizer();
        $sanitizer->removeRemoteReferences(true);
        $sanitizer->removeXMLTag(true);

        $clean = $sanitizer->sanitize($svg);

        if ($clean === false || trim($clean) === '') {
            throw InvalidLogoException::notAnSvg();
        }

        return $clean;
    }
}
