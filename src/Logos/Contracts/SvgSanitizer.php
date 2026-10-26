<?php

declare(strict_types=1);

namespace ExeQue\Qr\Logos\Contracts;

/**
 * Makes an untrusted SVG safe to inline in a page. The service provider binds
 * the one in config('qr.logos.sanitizer').
 */
interface SvgSanitizer
{
    /**
     * The SVG with everything unsafe removed.
     *
     * @throws \ExeQue\Qr\Exceptions\InvalidLogoException When it is not an SVG that can be made safe.
     */
    public function clean(string $svg): string;
}
