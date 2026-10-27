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
use Illuminate\Support\Facades\Process;

covers(Manifest::class, ManifestCommand::class, \ExeQue\Qr\Qr::class, QrServiceProvider::class);

pest()->group('manifest');

describe('the manifest', function (): void {
    it('holds every registered part, labeled, with paths about its middle', function (): void {
        $manifest = Qr::manifest('da');

        expect(array_keys($manifest))->toBe(['locale', 'modules', 'outerEyes', 'innerEyes', 'eyes', 'filters', 'scanSafety'])
            ->and($manifest['locale'])->toBe('da')
            ->and(array_keys($manifest['modules']))->toBe(Qr::modules()->names())
            ->and($manifest['modules']['square'])->toBe(['label' => 'Firkant', 'path' => 'M -0.5 -0.5 L 0.5 -0.5 L 0.5 0.5 L -0.5 0.5 Z', 'neighbors' => false, 'varies' => false])
            ->and($manifest['modules']['flowing-soft']['neighbors'])->toBeTrue()
            ->and($manifest['modules']['sketch']['varies'])->toBeTrue()
            ->and($manifest['outerEyes']['square']['path'])->toStartWith('M -3.5 -3.5')
            ->and($manifest['outerEyes']['leaf']['directional'])->toBeTrue()
            ->and($manifest['innerEyes']['circle']['directional'])->toBeFalse()
            ->and($manifest['eyes']['round'])->toBe(['label' => 'Rund', 'outer' => 'square', 'inner' => 'circle'])
            ->and($manifest['eyes']['hexagon-standing'])->toMatchArray(['outer' => 'hexagon-standing', 'inner' => 'hexagon-standing'])
            ->and(app()->getLocale())->toBe('en');
    });

    it('tells module filters from eye filters, and those that change colors', function (): void {
        Qr::filters()->register('needs-colors', \ExeQue\Qr\Filters\Palette::class);
        $filters = Qr::manifest()['filters'];

        expect($filters['mixed-rotation'])->toBe(['label' => 'Mixed rotation', 'module' => true, 'eye' => false, 'color' => false])
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

    it('writes the manifest as JSON to a file or the console', function (): void {
        $path = sys_get_temp_dir().'/qr-manifest-'.uniqid().'/manifest.json';

        $this->artisan('qr:manifest', ['--path' => $path, '--locale' => 'da'])->expectsOutputToContain('QR code manifest written')->assertSuccessful();
        $this->artisan('qr:manifest')->expectsOutputToContain('"locale": "en"')->assertSuccessful();

        expect(json_decode((string) file_get_contents($path), true)['modules']['square']['label'])->toBe('Firkant');

        unlink($path);
        rmdir(dirname($path));
    });
});

describe('the browser module', function (): void {
    it('judges colors as the server\'s guards do', function (): void {
        $pairs = [['#000000', '#ffffff'], ['#777777', '#ffffff'], ['#ffffff', '#111111'], ['#bbbbbb', '#ffffff'], ['#00000080', '#ffffff'], ['#1e1b4b', '#fef3c7'], ['#ffffff', '#ffffff00']];
        $module = dirname(__DIR__, 3).'/resources/js/laravel-qr.js';
        $script = 'import("'.$module.'").then((qr) => console.log(JSON.stringify('.json_encode($pairs).'.map(([a, b]) => [qr.contrast(a, b), qr.isLighter(a, b), qr.scannability({ foreground: a, background: b }).severity]))))';

        $result = Process::run(['node', '--input-type=module', '-e', $script]);
        $browser = json_decode($result->output(), true);

        foreach ($pairs as $index => [$color, $ground]) {
            $report = Qr::url('https://example.com')->paint($color)->background($ground)->scanSafetyReport();

            expect($browser[$index][0])->toEqualWithDelta(Contrast::ratio(new Hex($color), new Hex($ground)), 1e-9)
                ->and($browser[$index][1])->toBe(Contrast::lighter(new Hex($color), new Hex($ground)))
                ->and($browser[$index][2])->toBe($report->severity()->value === 'adjusted' ? 'fine' : $report->severity()->value);
        }
    })->skip(fn (): bool => trim((string) shell_exec('command -v node')) === '', 'needs Node');

    it('reads the colors it can, and gives labels from the manifest', function (): void {
        $module = dirname(__DIR__, 3).'/resources/js/laravel-qr.js';
        $manifest = json_encode(Qr::manifest('da'));
        $script = 'import("'.$module.'").then((qr) => { const m = '.$manifest.'; console.log(JSON.stringify([qr.parseColor("#abc"), qr.parseColor("#abcd"), qr.parseColor("rgb(10, 20, 30)"), qr.parseColor("rgba(10 20 30 / 50%)"), qr.parseColor("rgb(100% 0% 0%)"), qr.parseColor("transparent"), qr.parseColor("oklch(50% 0.1 20)"), qr.parseColor(null), qr.contrast("nope", "#fff"), qr.isLighter("#fff", "nope"), qr.scannability({ foreground: "nope" }).severity, qr.scannability({ foreground: "#fff", transparent: true }, m).problems, qr.scannability({ foreground: "#fff", background: "#000" }, { inverted: "throw" }).severity, qr.scannability({ foreground: "#eee" }, { min_contrast: null, inverted: null }).severity, qr.label(m, "modules", "dots-medium"), qr.label(m, "filters", "nope"), qr.label(null, "eyes", "heart")])) })';

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
            [['guard' => 'contrast', 'severity' => 'refused']],
            'refused',
            'fine',
            'Mellemstore prikker',
            'nope',
            'heart',
        ]);
    })->skip(fn (): bool => trim((string) shell_exec('command -v node')) === '', 'needs Node');
});
