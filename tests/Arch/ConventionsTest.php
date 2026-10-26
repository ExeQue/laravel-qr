<?php

declare(strict_types=1);

namespace Tests\Arch;

use ExeQue\Qr\Exceptions\QrExceptionInterface;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

pest()->group('arch');

describe('package conventions', function (): void {
    arch()->preset()->php();

    arch()->preset()->security();

    arch('every file declares strict types')
        ->expect('ExeQue\Qr')
        ->toUseStrictTypes();

    arch('classes are final')
        ->expect('ExeQue\Qr')
        ->classes()
        ->toBeFinal()
        ->ignoring('ExeQue\Qr\Facades');

    arch('contracts are interfaces')
        ->expect(['ExeQue\Qr\Contracts', 'ExeQue\Qr\Decoding\Contracts', 'ExeQue\Qr\Filters\Contracts', 'ExeQue\Qr\Gradients\Contracts', 'ExeQue\Qr\Shapes\Contracts'])
        ->toBeInterfaces();

    arch('enums are enums')
        ->expect([
            'ExeQue\Qr\Encoder\Enums',
            'ExeQue\Qr\Filters\Enums',
            'ExeQue\Qr\Gradients\Enums',
            'ExeQue\Qr\Rendering\Enums',
            'ExeQue\Qr\Shapes\Enums',
            'ExeQue\Qr\Writers\Svg\Enums',
        ])
        ->toBeEnums();

    arch('exceptions implement the package exception interface')
        ->expect('ExeQue\Qr\Exceptions')
        ->classes()
        ->toImplement(QrExceptionInterface::class);

    // The core layers use Laravel's contracts at most, not its classes.
    // Named colors and logos look up the application's names and sanitizer in the container,
    // and the automatic sanitizer has the container build the one it picks.
    arch('the core does not use Laravel')
        ->expect([
            'ExeQue\Qr\Encoder',
            'ExeQue\Qr\Colors',
            'ExeQue\Qr\Contracts',
            'ExeQue\Qr\Exceptions',
            'ExeQue\Qr\Geometry',
            'ExeQue\Qr\Gradients',
            'ExeQue\Qr\Rendering',
            'ExeQue\Qr\Shapes',
            'ExeQue\Qr\Support',
            'ExeQue\Qr\Writers',
        ])
        ->not->toUse(['Illuminate\Support', 'Illuminate\Container', 'Illuminate\Foundation', 'Illuminate\Http', 'Illuminate\Filesystem', 'Illuminate\Database'])
        ->ignoring(['ExeQue\Qr\Colors\Named', 'ExeQue\Qr\Logos\Logo', 'ExeQue\Qr\Logos\Sanitizers\AutoSvgSanitizer']);
});

describe('test conventions', function (): void {
    test('internal classes are marked @internal and public ones are not', function (): void {
        // Everything in these namespaces is public API...
        $publicNamespaces = [
            'Builders', 'Colors', 'Contracts', 'Decoding', 'Events', 'Exceptions', 'Facades', 'Filters', 'Icons', 'Logos', 'Payloads', 'Registries',
            'Geometry', 'Gradients', 'Monocle', 'Paints', 'Rendering', 'Shapes', 'Testing', 'Writers',
        ];

        // ...apart from traits in a Concerns namespace and these helpers.
        $internal = [
            'ExeQue\\Qr\\Builders\\Pipeline',
            'ExeQue\\Qr\\Builders\\Resolve',
            'ExeQue\\Qr\\Colors\\Component',
            'ExeQue\\Qr\\Colors\\Convert',
            'ExeQue\\Qr\\Colors\\Gamut',
            'ExeQue\\Qr\\Decoding\\Native\\Bitmap',
            'ExeQue\\Qr\\Decoding\\Native\\FinderLocator',
            'ExeQue\\Qr\\Decoding\\Native\\Perspective',
            'ExeQue\\Qr\\Decoding\\Native\\Sampler',
            'ExeQue\\Qr\\Decoding\\Native\\SegmentParser',
            'ExeQue\\Qr\\Decoding\\Native\\SymbolReader',
            'ExeQue\\Qr\\Gradients\\Interpolator',
            'ExeQue\\Qr\\Logos\\Knockout',
            'ExeQue\\Qr\\Logos\\SvgIds',
            'ExeQue\\Qr\\Monocle\\Timeline',
            'ExeQue\\Qr\\Rendering\\Guard',
            'ExeQue\\Qr\\Rendering\\StyleSerializer',
            'ExeQue\\Qr\\Writers\\Boxes',
            'ExeQue\\Qr\\Writers\\Raster\\Coverage',
            'ExeQue\\Qr\\Writers\\Raster\\Gd\\Metadata',
        ];

        // Elsewhere only these are.
        $public = ['ExeQue\\Qr\\Encoder\\Encoder', 'ExeQue\\Qr\\Encoder\\Matrix', 'ExeQue\\Qr\\Factory', 'ExeQue\\Qr\\Qr'];

        $files = Finder::create()->files()->in(dirname(__DIR__, 2).'/src')->name('*.php');

        foreach ($files as $file) {
            $class = 'ExeQue\\Qr\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            $namespace = explode('\\', $class)[2];

            $isPublic = in_array($class, $public, true)
                || str_contains($class, '\\Enums\\')
                || (in_array($namespace, $publicNamespaces, true) && ! str_contains($class, '\\Concerns\\') && ! in_array($class, $internal, true));
            $isInternal = str_contains((string) new ReflectionClass($class)->getDocComment(), '@internal');

            expect($isInternal)->toBe(! $isPublic, $isPublic ? "{$class} is public API but marked @internal" : "{$class} is internal but not marked @internal");
        }
    });

    test('every test file declares what it covers and mirrors its path in its namespace', function (): void {
        $root = dirname(__DIR__);
        $files = Finder::create()->files()->in([$root.'/Feature', $root.'/Unit'])->name('*Test.php');

        foreach ($files as $file) {
            $namespace = 'Tests\\'.str_replace('/', '\\', dirname(substr($file->getPathname(), strlen($root) + 1)));

            expect($file->getContents())
                ->toContain("namespace {$namespace};")
                ->toMatch('/^covers\(/m');
        }
    });

});

describe('opcache safety', function (): void {
    test('no list assignment reads what it assigns, which opcache gets wrong', function (): void {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__, 2).'/src', \FilesystemIterator::SKIP_DOTS));
        $offending = [];

        foreach ($files as $file) {
            foreach (file((string) $file) ?: [] as $number => $line) {
                if (preg_match('/^\s*\[([^\]]+)\]\s*=\s*\[(.*)\];/', $line, $match) !== 1) {
                    continue;
                }

                preg_match_all('/\$\w+/', $match[1], $targets);

                foreach ($targets[0] as $target) {
                    if (preg_match('/'.preg_quote($target, '/').'(?!\w)/', $match[2]) === 1) {
                        $offending[] = basename((string) $file).':'.($number + 1);
                    }
                }
            }
        }

        expect($offending)->toBe([]);
    });
});
