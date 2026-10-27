<?php

declare(strict_types=1);

namespace Tests\Feature\Manifest;

use ExeQue\Qr\Colors\Contrast;
use ExeQue\Qr\Colors\Hex;
use ExeQue\Qr\Console\ManifestCommand;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Manifest\Manifest;
use ExeQue\Qr\QrServiceProvider;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrEye;
use ExeQue\Qr\Shapes\InnerEyes\Square as SquareInner;
use ExeQue\Qr\Shapes\OuterEyes\Octagon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Process;

covers(Manifest::class, ManifestCommand::class, \ExeQue\Qr\Qr::class, QrServiceProvider::class, \ExeQue\Qr\View\Components\Manifest::class);

pest()->group('manifest');

describe('the manifest', function (): void {
    it('holds every registered part, with paths about its middle, and labels and messages by language', function (): void {
        $manifest = Qr::manifest(['da', 'en']);

        expect(array_keys($manifest))->toBe(['locales', 'modules', 'outerEyes', 'innerEyes', 'eyes', 'filters', 'scanSafety', 'labels', 'messages'])
            ->and($manifest['locales'])->toBe(['da', 'en'])
            ->and(array_keys($manifest['modules']))->toBe(Qr::modules()->names())
            ->and($manifest['modules']['square'])->toBe(['path' => 'M -0.5 -0.5 L 0.5 -0.5 L 0.5 0.5 L -0.5 0.5 Z', 'neighbors' => false, 'varies' => false])
            ->and($manifest['modules']['flowing-soft']['neighbors'])->toBeTrue()
            ->and($manifest['modules']['sketch']['varies'])->toBeTrue()
            ->and($manifest['outerEyes']['square']['path'])->toStartWith('M -3.5 -3.5')
            ->and($manifest['outerEyes']['leaf']['directional'])->toBeTrue()
            ->and($manifest['innerEyes']['circle']['directional'])->toBeFalse()
            ->and($manifest['eyes']['round'])->toBe(['outer' => 'square', 'inner' => 'circle'])
            ->and($manifest['eyes']['hexagon-standing'])->toBe(['outer' => 'hexagon-standing', 'inner' => 'hexagon-standing'])
            ->and($manifest['labels']['da']['modules']['square'])->toBe('Firkant')
            ->and($manifest['labels']['en']['eyes']['round'])->toBe('Round')
            ->and(array_keys($manifest['labels']['da']))->toBe(['modules', 'outerEyes', 'innerEyes', 'eyes', 'filters'])
            ->and($manifest['messages']['en']['inverted'])->toBe(trans('qr::validation.scannable.inverted', [], 'en'))
            ->and($manifest['messages']['da']['contrast'])->toBe(trans('qr::validation.scannable.contrast', [], 'da'))
            ->and(Qr::manifest()['locales'])->toBe(['en'])
            ->and(Qr::manifest('da')['locales'])->toBe(['da'])
            ->and(Qr::manifest('*')['locales'])->toHaveCount(29)
            ->and(app()->getLocale())->toBe('en');
    });

    it('tells module filters from eye filters, and those that change colors', function (): void {
        Qr::filters()->register('needs-colors', \ExeQue\Qr\Filters\Palette::class);
        $filters = Qr::manifest()['filters'];

        expect($filters['mixed-rotation'])->toBe(['module' => true, 'eye' => false, 'color' => false])
            ->and($filters['palette']['color'])->toBeTrue()
            ->and($filters['eye-paint'])->toMatchArray(['module' => false, 'eye' => true, 'color' => true])
            ->and($filters['compound'])->toMatchArray(['module' => true, 'eye' => true])
            ->and($filters['needs-colors'])->toMatchArray(['module' => true, 'color' => null]);
    });

    it('names no part a preset uses that no name stands for', function (): void {
        Qr::eyes()->register('own', new class () implements ProvidesQrEye {
            public function qrOuterEye(): \ExeQue\Qr\Shapes\Contracts\OuterEyeShape
            {
                return new Octagon(cut: 2.1);
            }

            public function qrInnerEye(): \ExeQue\Qr\Shapes\Contracts\InnerEyeShape
            {
                return new SquareInner();
            }
        });

        expect(Qr::manifest()['eyes']['own'])->toMatchArray(['outer' => null, 'inner' => 'square']);
    });

    it('gives the scan guards\' limits as the config has them', function (): void {
        config(['qr.scan_safety.min_contrast' => 4.5, 'qr.scan_safety.inverted' => null]);

        expect(Qr::manifest()['scanSafety'])->toMatchArray(['min_contrast' => 4.5, 'inverted' => null, 'on_violation' => 'throw', 'assumed_background' => '#ffffff']);
    });

    it('writes the manifest as JSON to a file or the console, in the languages asked for', function (): void {
        $path = sys_get_temp_dir().'/qr-manifest-'.uniqid().'/manifest.json';

        $this->artisan('qr:manifest', ['--path' => $path, '--locale' => ['da', 'sv']])->expectsOutputToContain('QR code manifest written')->assertSuccessful();
        $this->artisan('qr:manifest')->expectsOutputToContain('"locales": [')->assertSuccessful();
        $this->artisan('qr:manifest', ['--all-locales' => true])->expectsOutputToContain('"uk"')->assertSuccessful();

        expect(json_decode((string) file_get_contents($path), true)['labels']['da']['modules']['square'])->toBe('Firkant')
            ->and(array_keys(json_decode((string) file_get_contents($path), true)['labels']))->toBe(['da', 'sv']);

        unlink($path);
        rmdir(dirname($path));
    });

    it('puts the manifest in the page in the request\'s language, safe inside its script element', function (): void {
        app()->setLocale('da');
        Qr::modules()->register('</script><b>', \ExeQue\Qr\Shapes\Modules\Square::class);

        $html = Blade::render('<x-qr::manifest />');
        preg_match('/^<script type="application\/json" id="laravel-qr-manifest">(.*)<\/script>$/s', trim($html), $match);

        expect($match)->toHaveCount(2)
            ->and(json_decode($match[1], true)['locales'])->toBe(['da'])
            ->and(json_decode($match[1], true)['modules'])->toHaveKey('</script><b>')
            ->and(Blade::render('<x-qr::manifest :locales="[\'en\', \'da\']" id="qr" />'))->toContain('id="qr"')->toContain('"locales":["en","da"]');
    });

    it('caches the manifest in a store of its own, per language, until cleared', function (?int $ttl): void {
        config(['qr.manifest.cache' => true, 'qr.manifest.store' => 'array', 'qr.manifest.ttl' => $ttl]);
        $first = Qr::manifest('da');
        Qr::modules()->register('late', \ExeQue\Qr\Shapes\Modules\Square::class);

        expect(Qr::manifest('da'))->toBe($first)->not->toHaveKey('modules.late')
            ->and(Qr::manifest('en')['modules'])->toHaveKey('late');

        Qr::flush();

        expect(Qr::manifest('da')['modules'])->toHaveKey('late');
    })->with([[null], [3600]]);
});

describe('the browser module', function (): void {
    it('judges colors as the server\'s guards do', function (): void {
        $pairs = [['#000000', '#ffffff'], ['#777777', '#ffffff'], ['#ffffff', '#111111'], ['#bbbbbb', '#ffffff'], ['#00000080', '#ffffff'], ['#1e1b4b', '#fef3c7'], ['#ffffff', '#ffffff00']];
        $module = dirname(__DIR__, 3).'/resources/js/laravel-qr.js';
        $script = 'import("'.$module.'").then((qr) => console.log(JSON.stringify('.json_encode($pairs).'.map(([a, b]) => [qr.contrast(a, b), qr.isLighter(a, b), qr.scannability({ foreground: a, background: b }, null).severity]))))';

        $result = Process::run(['node', '--input-type=module', '-e', $script]);
        $browser = json_decode($result->output(), true);

        foreach ($pairs as $index => [$color, $ground]) {
            $report = Qr::url('https://example.com')->paint($color)->background($ground)->scanSafetyReport();

            expect($browser[$index][0])->toEqualWithDelta(Contrast::ratio(new Hex($color), new Hex($ground)), 1e-9)
                ->and($browser[$index][1])->toBe(Contrast::lighter(new Hex($color), new Hex($ground)))
                ->and($browser[$index][2])->toBe($report->severity()->value === 'adjusted' ? 'fine' : $report->severity()->value);
        }
    })->skip(fn (): bool => trim((string) shell_exec('command -v node')) === '', 'needs Node');

    it('reads the colors it can, and the manifest in the page in the closest language', function (): void {
        $module = dirname(__DIR__, 3).'/resources/js/laravel-qr.js';
        $page = json_encode(Qr::manifest(['en', 'da']));
        $script = 'globalThis.document = { documentElement: { lang: "da-DK" }, getElementById: (id) => id === "laravel-qr-manifest" ? { textContent: '.json_encode($page).' } : null }; '
            .'import("'.$module.'").then((qr) => console.log(JSON.stringify([qr.parseColor("#abc"), qr.parseColor("#abcd"), qr.parseColor("rgb(10, 20, 30)"), qr.parseColor("rgba(10 20 30 / 50%)"), qr.parseColor("rgb(100% 0% 0%)"), qr.parseColor("transparent"), qr.parseColor("oklch(50% 0.1 20)"), qr.parseColor(null), qr.contrast("nope", "#fff"), qr.isLighter("#fff", "nope"), qr.scannability({ foreground: "nope" }).severity, '
            .'qr.scannability({ foreground: "#fff", transparent: true }).problems, qr.scannability({ foreground: "#fff", background: "#000", locale: "en" }, { scanSafety: { inverted: "throw" } }).problems, qr.scannability({ foreground: "#eee" }, { scanSafety: { min_contrast: null, inverted: null } }).severity, '
            .'qr.label("modules", "dots-medium"), qr.label("modules", "dots-medium", { locale: "en" }), qr.label("filters", "nope"), qr.label("eyes", "heart", { manifest: null }), qr.message("inverted", { locale: "fr" }), qr.message("nope"), qr.localeOf(null), qr.manifest("other"), qr.manifest() === qr.manifest()])))';

        $result = json_decode(Process::run(['node', '--input-type=module', '-e', $script])->output(), true);

        expect($result)->toBe([
            ['r' => 170, 'g' => 187, 'b' => 204, 'a' => 1],
            ['r' => 170, 'g' => 187, 'b' => 204, 'a' => 221 / 255],
            ['r' => 10, 'g' => 20, 'b' => 30, 'a' => 1],
            ['r' => 10, 'g' => 20, 'b' => 30, 'a' => 0.5],
            ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1],
            ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0],
            null,
            null,
            null,
            null,
            'unknown',
            [['guard' => 'contrast', 'severity' => 'refused', 'message' => trans('qr::validation.scannable.contrast', [], 'da')]],
            [['guard' => 'inverted', 'severity' => 'refused', 'message' => null]],
            'fine',
            'Mellemstore prikker',
            'Medium dots',
            'nope',
            'heart',
            trans('qr::validation.scannable.inverted', [], 'en'),
            null,
            null,
            null,
            true,
        ]);
    })->skip(fn (): bool => trim((string) shell_exec('command -v node')) === '', 'needs Node');
});
