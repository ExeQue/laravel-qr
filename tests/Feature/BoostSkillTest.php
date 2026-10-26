<?php

declare(strict_types=1);

namespace Tests\Feature;

use ExeQue\Qr\Registries\ModuleRegistry;
use Illuminate\Support\Facades\Blade;

covers(ModuleRegistry::class);

it('renders the Boost skill with the names this application has', function (): void {
    \ExeQue\Qr\Facades\Qr::modules()->register('pill', \ExeQue\Qr\Shapes\Modules\Dot::class);
    \ExeQue\Qr\Facades\Qr::styles()->register('brand', fn () => \ExeQue\Qr\Facades\Qr::style()->modules('dots-medium'));
    app(\ExeQue\Qr\Colors\ColorNames::class)->define('brand', '#be123c');

    $skill = Blade::render((string) file_get_contents(dirname(__DIR__, 2).'/resources/boost/skills/laravel-qr/SKILL.blade.php'));

    expect($skill)->toStartWith("---\nname: laravel-qr")
        ->toContain('`flowing-medium`')
        ->toContain('`pill`')
        ->toContain('Styles of this application: `brand`')
        ->toContain('Color names of this application: `brand` (#be123c)')
        ->toContain("'brand' => Style::make()->modules('dots-medium')")
        ->toContain('`drops` (side 0.94; point top-left|top-right|bottom-right|bottom-left|random; seed 0)')
        ->toContain('`round-corner` (outer [0, 0, 0, 3.5]; hole [0, 0, 0, 2.5])')->toContain('`pill` (vertical true)')
        ->toContain('<x-qr :content="$url" class="size-40" />');
});

it('carries the documentation as its references, as the docs say it now', function (): void {
    require_once dirname(__DIR__, 2).'/scripts/boost-references.php';

    $references = dirname(__DIR__, 2).'/resources/boost/skills/laravel-qr/references';
    $carried = array_map(file_get_contents(...), array_combine(array_map(basename(...), glob("{$references}/*.md") ?: []), glob("{$references}/*.md") ?: []));

    $expected = boostReferences(dirname(__DIR__, 2));
    ksort($carried);
    ksort($expected);

    expect($carried)->toBe($expected, 'The skill references drifted from the docs: run composer boost:references.')
        ->and(implode('', $carried))->not->toContain('](images/');
});
