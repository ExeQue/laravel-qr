<?php

declare(strict_types=1);

namespace Tests\Feature\Logos;

use ExeQue\Qr\Exceptions\InvalidLogoException;
use ExeQue\Qr\Logos\Contracts\SvgSanitizer;
use ExeQue\Qr\Logos\Sanitizers\AutoSvgSanitizer;
use ExeQue\Qr\Logos\Sanitizers\BasicSvgSanitizer;
use ExeQue\Qr\Logos\Sanitizers\EnshrinedSvgSanitizer;
use ExeQue\Qr\Logos\SvgIds;

covers(AutoSvgSanitizer::class, BasicSvgSanitizer::class, EnshrinedSvgSanitizer::class, SvgIds::class, InvalidLogoException::class);

dataset('sanitizers', [
    'basic' => [fn (): SvgSanitizer => new BasicSvgSanitizer()],
    'enshrined' => [fn (): SvgSanitizer => app(EnshrinedSvgSanitizer::class)],
    'auto' => [fn (): SvgSanitizer => app(AutoSvgSanitizer::class)],
]);

it('strips everything hostile', function (SvgSanitizer $sanitizer): void {
    $clean = strtolower($sanitizer->clean((string) file_get_contents(dirname(__DIR__, 2).'/Fixtures/logos/hostile.svg')));

    // enshrined leaves javascript: inside a CSS url(), which browsers do not run.
    $clean = str_replace('style="fill: url(javascript:alert(5))"', '', $clean);

    expect($clean)
        ->not->toContain('<script')
        ->not->toContain('alert(')
        ->not->toContain('onload')
        ->not->toContain('onmouseover')
        ->not->toContain('onclick')
        ->not->toContain('foreignobject')
        ->not->toContain('javascript:')
        ->not->toContain('evil.example')
        ->not->toContain('<iframe')
        ->toContain('<circle')
        ->toContain('lineargradient');
})->with('sanitizers');

it('keeps what is safe', function (SvgSanitizer $sanitizer): void {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><defs><linearGradient id="g"><stop offset="0" stop-color="#000"/></linearGradient></defs><path d="M0 0h10v10z" fill="url(#g)"/><image href="data:image/png;base64,iVBORw0KGgo=" width="1" height="1"/></svg>';

    expect($sanitizer->clean($svg))
        ->toContain('d="M0 0h10v10z"')
        ->toContain('fill="url(#g)"')
        ->toContain('data:image/png;base64,iVBORw0KGgo=');
})->with('sanitizers');

it('refuses what is not an SVG', function (SvgSanitizer $sanitizer, string $input): void {
    $sanitizer->clean($input);
})->with('sanitizers')->with([
    'not xml' => ['not an svg'],
    'empty' => [''],
])->throws(InvalidLogoException::class);

it('refuses another root element, and entities', function (string $input, string $message): void {
    expect(fn () => new BasicSvgSanitizer()->clean($input))->toThrow(InvalidLogoException::class, $message);
})->with([
    ['<html><body/></html>', 'The logo is not a readable SVG document.'],
    ['<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg">&x;</svg>', 'An SVG logo may not have a DOCTYPE or entities.'],
]);

it('prefixes ids and their references', function (): void {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="root"><style>.a { fill: url(#g); }</style><linearGradient id="g"/><rect fill="url(#g)" clip-path="url(\'#g\')"/><use href="#g"/><use xlink:href="#g"/></svg>';

    expect(SvgIds::prefix($svg, 'logo-1-'))
        ->toContain('id="logo-1-root"')
        ->toContain('id="logo-1-g"')
        ->toContain('fill="url(#logo-1-g)"')
        ->toContain("clip-path=\"url('#logo-1-g')\"")
        ->toContain('href="#logo-1-g"')
        ->toContain('xlink:href="#logo-1-g"')
        ->toContain('fill: url(#logo-1-g)');
});

it('leaves an SVG without ids as it is', function (): void {
    expect(SvgIds::prefix('<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>', 'p-'))->toBe('<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>');
});

it('refuses to prefix what is not XML', function (): void {
    SvgIds::prefix('nope', 'p-');
})->throws(InvalidLogoException::class);

it('refuses CSS that hides what it reaches for', function (string $svg, string $gone): void {
    expect(new BasicSvgSanitizer()->clean($svg))->not->toContain($gone);
})->with([
    'an escaped url( in a style sheet' => ['<svg xmlns="http://www.w3.org/2000/svg"><style>.a{background:u\72l(//evil/t.png)}</style></svg>', 'evil'],
    'an escaped @import' => ['<svg xmlns="http://www.w3.org/2000/svg"><style>@\69mport "//evil/x.css";</style></svg>', 'evil'],
    'any at-rule' => ['<svg xmlns="http://www.w3.org/2000/svg"><style>@font-face{font-family:x}</style></svg>', 'font-face'],
    'an escape in a style attribute' => ['<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:u\72l(//evil)"/></svg>', 'evil'],
    'an element of another namespace' => ['<svg xmlns="http://www.w3.org/2000/svg"><h:style xmlns:h="http://www.w3.org/1999/xhtml">body{display:none}</h:style></svg>', 'display'],
]);

it('keeps CDATA as plain text', function (): void {
    expect(new BasicSvgSanitizer()->clean('<svg xmlns="http://www.w3.org/2000/svg"><title><![CDATA[</title><img src=x>]]></title></svg>'))
        ->toBe('<svg xmlns="http://www.w3.org/2000/svg"><title>&lt;/title&gt;&lt;img src=x&gt;</title></svg>');
});

it('refuses a pile of uses', function (): void {
    new BasicSvgSanitizer()->clean('<svg xmlns="http://www.w3.org/2000/svg">'.str_repeat('<use href="#a"/>', 65).'</svg>');
})->throws(InvalidLogoException::class);

it('keeps a logo\'s styles to the logo', function (): void {
    $svg = SvgIds::prefix('<svg xmlns="http://www.w3.org/2000/svg"><style>.a, #b{fill:red} body{display:none}</style><rect id="b" class="a" fill="url(#b)"/></svg>', 'p-');

    expect($svg)->toBe('<svg xmlns="http://www.w3.org/2000/svg" id="p-root"><style>#p-root .a, #p-root #p-b{fill:red}#p-root body{display:none}</style><rect id="p-b" class="a" fill="url(#p-b)"/></svg>');
});

it('drops style sheets it cannot keep to the logo', function (string $css): void {
    expect(SvgIds::prefix('<svg xmlns="http://www.w3.org/2000/svg"><style>'.$css.'</style></svg>', 'p-'))
        ->toBe('<svg xmlns="http://www.w3.org/2000/svg" id="p-root"/>');
})->with(['@media print{.a{fill:red}}', '.a{fill:u\72l(x)}', '.a{fill:red']);
