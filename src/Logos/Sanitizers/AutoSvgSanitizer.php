<?php

declare(strict_types=1);

namespace ExeQue\Qr\Logos\Sanitizers;

use enshrined\svgSanitize\Sanitizer;
use ExeQue\Qr\Logos\Contracts\SvgSanitizer;
use Illuminate\Contracts\Container\Container;

/**
 * Sanitizes with enshrined/svg-sanitize when it is installed, and with the
 * package's own allowlist when it is not; the container builds the one chosen.
 */
final readonly class AutoSvgSanitizer implements SvgSanitizer
{
    private SvgSanitizer $sanitizer;

    public function __construct(Container $container)
    {
        $this->sanitizer = $container->make(class_exists(Sanitizer::class) ? EnshrinedSvgSanitizer::class : BasicSvgSanitizer::class);
    }

    public function clean(string $svg): string
    {
        return $this->sanitizer->clean($svg);
    }
}
