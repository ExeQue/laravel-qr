<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use ExeQue\Qr\Console\IconsCommand;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Registries\Concerns\IsRegistry;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;
use ExeQue\Qr\Shapes\Modules\Square;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

covers(IconsCommand::class, IsRegistry::class);

pest()->group('console');

final class DiamondModule implements ModuleShape
{
    public function draw(ModuleContext $cell): Path
    {
        return Path::polygon([[0, -0.5], [0.5, 0], [0, 0.5], [-0.5, 0]]);
    }
}

beforeEach(function (): void {
    $this->directory = sys_get_temp_dir().'/qr-icons-'.bin2hex(random_bytes(4));
});

afterEach(function (): void {
    File::deleteDirectory($this->directory);
});

describe('the icons command', function (): void {
    it('keeps the shipped icons in step with the shapes', function (): void {
        $this->artisan('qr:icons', ['--built-in' => true, '--path' => $this->directory])->assertSuccessful();

        $shipped = dirname(__DIR__, 3).'/resources/icons';
        $files = iterator_to_array(Finder::create()->files()->in($shipped)->name('*.svg'), false);

        expect($files)->not->toBeEmpty()
            ->and(iterator_count(Finder::create()->files()->in($this->directory)))->toBe(count($files));

        foreach ($files as $file) {
            expect(file_get_contents("{$this->directory}/{$file->getRelativePathname()}"))
                ->toBe($file->getContents(), "resources/icons/{$file->getRelativePathname()} is out of date: run composer icons.");
        }
    });

    it('writes icons of the application\'s own shapes only', function (): void {
        Qr::modules()->register('diamond', DiamondModule::class);
        Qr::modules()->register('square', fn (): ModuleShape => new Square());
        Qr::outerEyes()->register('own-square', \ExeQue\Qr\Shapes\OuterEyes\Square::class);

        $this->artisan('qr:icons', ['--path' => $this->directory])
            ->expectsOutputToContain('3 QR code shape icons written')
            ->assertSuccessful();

        expect(file_get_contents("{$this->directory}/modules/diamond.svg"))->toContain('viewBox="0 0 3 3"')
            ->and("{$this->directory}/modules/square.svg")->toBeFile()
            ->and("{$this->directory}/outer-eyes/own-square.svg")->toBeFile()
            ->and("{$this->directory}/eyes")->not->toBeDirectory()
            ->and(Qr::modules()->isBuiltIn('square'))->toBeFalse()
            ->and(Qr::innerEyes()->isBuiltIn('square'))->toBeTrue()
            ->and(Qr::innerEyes()->isBuiltIn('nope'))->toBeFalse();
    });

    it('writes to resources/svg/qr by default', function (): void {
        Qr::modules()->register('diamond', DiamondModule::class);
        $this->directory = resource_path('svg');

        $this->artisan('qr:icons')->assertSuccessful();

        expect("{$this->directory}/qr/modules/diamond.svg")->toBeFile();
    });

    it('keeps icon files inside the directory, whatever the name', function (): void {
        Qr::modules()->register('../up/and away', DiamondModule::class);

        $this->artisan('qr:icons', ['--path' => $this->directory])->assertSuccessful();

        expect("{$this->directory}/modules/up-and-away.svg")->toBeFile()
            ->and(Qr::writers()->isBuiltIn('svg'))->toBeTrue();
    });

});
