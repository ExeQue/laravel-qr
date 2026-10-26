<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    |
    | What every code starts from. Each color takes any color notation; the
    | module and eye take a registered name, an enum case or a class name. A
    | style, if set, is a registered style merged in on top of these.
    |
    */

    'defaults' => [
        'error_correction' => ExeQue\Qr\Encoder\Enums\ErrorCorrection::Medium,
        'margin' => 4,
        'size' => 600,
        'foreground' => '#000000',
        'background' => '#ffffff',
        'module' => ExeQue\Qr\Shapes\Enums\Module::Square,
        'eye' => ExeQue\Qr\Shapes\Enums\Eye::Square,
        'style' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Written codes can be cached, per code with ->cache() or for every code
    | here. Within a request a code is written once whatever this says. Raise
    | the version to make every cached code stale; so does php artisan qr:clear.
    |
    */

    'cache' => [
        'enabled' => (bool) env('QR_CACHE', false),
        'store' => env('QR_CACHE_STORE'),
        'ttl' => 604800,
        'prefix' => 'qr',
        'version' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Scan safety
    |--------------------------------------------------------------------------
    |
    | Limits that keep a styled code scannable, applied after the filters. Too
    | little contrast or too large a logo throws, or only warns with 'warn'.
    | Null turns a limit off.
    |
    */

    'scan_safety' => [
        'min_contrast' => 3.0,
        'on_violation' => 'throw',
        'min_margin' => 2,
        'max_offset' => 0.15,
        'min_scale' => 0.6,
        'max_scale' => 1.25,
        'max_skew' => 20,
        'max_skip' => 0.25,
        'max_logo_share' => 0.6,
    ],

    /*
    |--------------------------------------------------------------------------
    | Attribution
    |--------------------------------------------------------------------------
    |
    | Written into every code's file - XMP in images, <metadata> in SVG - unless
    | a code sets its own: title, author, description, copyright, url, keywords.
    |
    */

    'metadata' => [
        'author' => null,
        'copyright' => null,
        'url' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Writers
    |--------------------------------------------------------------------------
    */

    'svg' => [
        'precision' => 3,
        'xml_declaration' => false,
    ],

    // What draws PNG, JPEG and WebP: 'imagick', 'gd', or 'auto' for Imagick when it is loaded,
    // else GD. GD needs no ImageMagick, but cannot draw SVG logos.
    'raster' => [
        'driver' => env('QR_RASTER_DRIVER', 'auto'),
    ],

    'png' => [
        'dpi' => null,
        // 0-9: how hard PNGs are packed; higher is smaller and slower, the image the same.
        'compression_level' => 6,
    ],

    /*
    |--------------------------------------------------------------------------
    | Streams
    |--------------------------------------------------------------------------
    |
    | Written codes are held in temporary streams: in memory while small, in a
    | temporary file beyond max_memory bytes.
    |
    */

    'streams' => [
        'max_memory' => (int) env('QR_STREAM_MAX_MEMORY', 2 * 1024 * 1024),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logos
    |--------------------------------------------------------------------------
    |
    | SVG logos are sanitized before they are used, by a class implementing
    | ExeQue\Qr\Logos\Contracts\SvgSanitizer. The default uses
    | enshrined/svg-sanitize when it is installed - recommended for logos from
    | people you do not know - and the package's own, strict but simple,
    | allowlist when it is not.
    |
    */

    'logos' => [
        'sanitizer' => ExeQue\Qr\Logos\Sanitizers\AutoSvgSanitizer::class,
    ],

    'logo' => [
        // A code with a logo gets error correction High, unless it sets its own.
        'auto_error_correction' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Reading codes
    |--------------------------------------------------------------------------
    |
    | Qr::read() reads the code in an image with the decoder named here:
    | 'zxing' (zxing-cpp through Node: `npm install zxing-wasm`, plus
    | @resvg/resvg-wasm to read SVG - the most capable), 'native' (the
    | package's own, in PHP, with Imagick - no Node needed), or 'stack', which
    | tries the decoders listed in turn until one reads the code, moving past
    | any that is not installed. Your own decoders go under 'decoders' below.
    |
    */

    'reading' => [
        'decoder' => env('QR_DECODER', 'stack'),
        'stack' => ['zxing', 'native'],
        'zxing' => [
            // What runs Node; the worker script and node_modules follow. To have npx
            // fetch the packages instead of installing them:
            // ['npx', '--yes', '-p', 'zxing-wasm@2.2.4', '-p', '@resvg/resvg-wasm@2.6.2', 'node']
            'command' => ['node'],
            // Where zxing-wasm is installed; null for the application's own node_modules.
            'node_modules' => null,
            'timeout' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monocle
    |--------------------------------------------------------------------------
    |
    | With exeque/laravel-monocle installed, every request's codes show in its
    | own "qr" section: timings, cache use and scan guard changes, never the
    | content. Renders slower than slow_render_ms are flagged.
    |
    | @see https://github.com/ExeQue/laravel-monocle
    |
    */

    'monocle' => [
        'enabled' => (bool) env('QR_MONOCLE', true),
        'slow_render_ms' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Color names
    |--------------------------------------------------------------------------
    |
    | Names of your own, usable wherever a color is, next to the CSS names:
    | 'brand' => '#be123c' makes ->paint('brand') work. The value is any color
    | notation, a color instance such as Oklch::make(0.55, 0.24, 293), or an
    | array such as ['r' => 190, 'g' => 18, 'b' => 60]; all of them survive
    | php artisan config:cache. CSS names cannot be redefined.
    |
    */

    'colors' => [
        // As a string, in any CSS notation:
        // 'brand' => '#be123c',
        // 'ink' => 'oklch(0.25 0.05 280)',
        // As a color class:
        // 'accent' => ExeQue\Qr\Colors\Hex::make('#0f766e'),
        // 'highlight' => ExeQue\Qr\Colors\Oklch::make(0.55, 0.24, 293),
        // 'sand' => ExeQue\Qr\Colors\Rgb::make(240, 222, 196),
        // As an array:
        // 'paper' => ['r' => 255, 'g' => 251, 'b' => 245],
    ],

    /*
    |--------------------------------------------------------------------------
    | Registries
    |--------------------------------------------------------------------------
    |
    | Parts of your own, by name, next to the built-in ones: a name => a class,
    | a name => [class, ...options] for a class with fixed options, or just an
    | enum class to register every case under its value. A name registered in
    | code (Qr::modules()->register(...)) wins over one here, which wins over a
    | built-in one. Closures and instances belong in code, not here.
    |
    |   'modules' => ['brush' => App\Qr\BrushModule::class],
    |   'filters' => ['brand-jitter' => [ExeQue\Qr\Filters\Jitter::class, 'rotate' => 6]],
    |
    */

    'modules' => [],

    'outer_eyes' => [],

    'inner_eyes' => [],

    'eyes' => [],

    'filters' => [],

    'gradients' => [],

    'payloads' => [],

    'writers' => [],

    'decoders' => [],

    /*
    |--------------------------------------------------------------------------
    | Styles
    |--------------------------------------------------------------------------
    |
    | Reusable designs by name, as Style::toArray() gives them, optionally
    | building on others with 'extends'; or an enum class implementing
    | ExeQue\Qr\Rendering\Contracts\ProvidesQrStyle, every case under its value.
    | Shapes, filters and gradients go by their registered names, colors by
    | any notation or a name from 'colors'. Style::make() takes the style
    | builder's methods, kept as plain values and built when first used. A
    | class implementing ProvidesQrStyle works too. Use one with ->style('brand').
    |
    */

    'styles' => [
        // With the style builder's methods, built when the style is first used:
        // 'brand' => ExeQue\Qr\Rendering\Style::make()->modules('dots-medium')->eyes('rounded')->paint('brand')->margin(2),
        // Or as plain values:
        // 'classic' => [
        //     'modules' => ['shape' => 'dots-medium', 'paint' => 'brand'],
        //     'eyes' => [
        //         'top-left' => ['outer' => ['shape' => 'rounded'], 'inner' => ['shape' => 'circle']],
        //         'top-right' => ['outer' => ['shape' => 'rounded'], 'inner' => ['shape' => 'circle']],
        //         'bottom-left' => ['outer' => ['shape' => 'rounded'], 'inner' => ['shape' => 'circle']],
        //     ],
        //     'background' => 'paper',
        //     'margin' => 2,
        // ],
        // Or a class implementing ProvidesQrStyle:
        // 'event' => App\Qr\EventStyle::class,
        // Building on others:
        // 'launch' => [
        //     'extends' => 'brand',
        //     'modules' => ['paint' => [
        //         'kind' => 'linear',
        //         'angle' => 135,
        //         'stops' => [['color' => 'brand', 'position' => 0], ['color' => 'ink', 'position' => 1]],
        //     ]],
        //     'filters' => [['name' => 'mixed-rotation', 'options' => ['min' => -15, 'max' => 15]]],
        // ],
    ],

];
