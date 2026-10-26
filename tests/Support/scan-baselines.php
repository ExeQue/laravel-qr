<?php

declare(strict_types=1);

/*
 * Every built-in shape must scan, as SVG and as a 600 pixel PNG, holding a link and a Wi-Fi
 * login - apart from the cases listed here, as "content:format", or "content:format:mirrored" for
 * an eye preset in a mirrored style; "@im6" or "@im7" after a case limits it to that ImageMagick, whose
 * rasterizing differs at the edges. A shape that starts to scan
 * where it is listed fails the test too, so this list only ever shrinks on purpose.
 */

return [
    'modules' => [],
    'outer-eyes' => [
        // A round ring blurs the finder's 1:1:3:1:1 runs once the code is dense and rasterized.
        'circle' => ['wifi:png@im7'],
    ],
    'inner-eyes' => [],
    'eyes' => [
        // Turned a quarter by mirrored(), its points stand up and down; it is symmetric and needs no mirroring.
        'hexagon' => ['wifi:svg:mirrored', 'wifi:png:mirrored@im6'],
    ],
];
