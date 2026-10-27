<?php

declare(strict_types=1);

namespace Tests\Feature\Builders;

use ExeQue\Qr\Builders\BackgroundBuilder;
use ExeQue\Qr\Builders\Concerns\BuildsStyle;
use ExeQue\Qr\Builders\EyeBuilder;
use ExeQue\Qr\Builders\EyesBuilder;
use ExeQue\Qr\Builders\LogoBuilder;
use ExeQue\Qr\Builders\ModulesBuilder;
use ExeQue\Qr\Builders\PendingQr;
use ExeQue\Qr\Builders\Rendered;
use ExeQue\Qr\Builders\Resolve;
use ExeQue\Qr\Builders\StyleBuilder;
use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Encoder\Enums\Mask;
use ExeQue\Qr\Exceptions\InvalidLogoException;
use ExeQue\Qr\Exceptions\ResolveException;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Filters\Enums\Filter;
use ExeQue\Qr\Filters\MixedSize;
use ExeQue\Qr\Gradients\LinearGradient;
use ExeQue\Qr\Logos\Contracts\ProvidesQrLogo;
use ExeQue\Qr\Logos\Enums\LogoShape;
use ExeQue\Qr\Logos\Logo;
use ExeQue\Qr\Payloads\Contracts\Payload;
use ExeQue\Qr\Payloads\Contracts\ProvidesQrContent;
use ExeQue\Qr\Qr as QrManager;
use ExeQue\Qr\Rendering\Contracts\ProvidesQrStyle;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Rendering\ScanSafety;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Shapes\Enums\Eye;
use ExeQue\Qr\Shapes\Enums\InnerEye;
use ExeQue\Qr\Shapes\Enums\Module;
use ExeQue\Qr\Shapes\Enums\OuterEye;
use ExeQue\Qr\Shapes\Modules\Square;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Imagick;
use SplFileInfo;

covers(
    QrManager::class,
    PendingQr::class,
    StyleBuilder::class,
    BuildsStyle::class,
    Resolve::class,
    Rendered::class,
    ModulesBuilder::class,
    EyesBuilder::class,
    EyeBuilder::class,
    BackgroundBuilder::class,
    LogoBuilder::class,
    ResolveException::class,
    InvalidLogoException::class,
    ScanSafety::class,
    \ExeQue\Qr\Rendering\EyeStyle::class,
    \ExeQue\Qr\Exceptions\InvalidOptionException::class,
    \ExeQue\Qr\Writers\Raster\Enums\Driver::class,
);

pest()->group('builder');

const BUILDER_URL = 'https://example.com/e/ABCD1234';

function logoFixture(): string
{
    return dirname(__DIR__, 2).'/Fixtures/logos/badge.svg';
}

describe('making codes', function (): void {
    it('makes a code from text, a payload or anything providing content', function (string|Payload|ProvidesQrContent $content): void {
        expect(Qr::make($content)->text())->toBe(BUILDER_URL)
            ->and(Qr::make($content)->svg())->toDecodeAs(BUILDER_URL);
    })->with([
        'text' => [BUILDER_URL],
        'payload' => [fn () => new class () implements Payload {
            public function toString(): string
            {
                return BUILDER_URL;
            }
        }],
        'provider' => [fn () => new class () implements ProvidesQrContent {
            public function qrContent(): string
            {
                return BUILDER_URL;
            }
        }],
    ])->group('scan');

    it('starts from the config\'s defaults', function (): void {
        config()->set('qr.defaults', ['margin' => 2, 'paint' => '#1c1917', 'background' => null, 'error_correction' => 'Q', 'module' => 'square', 'eye' => Eye::Square]);
        Qr::styles()->register('wide', ['margin' => 6]);

        $code = Qr::make(BUILDER_URL);

        expect($code->toStyle()->margin)->toBe(2)
            ->and($code->toStyle()->modules->paint->toCss())->toBe('#1c1917')
            ->and($code->toStyle()->background)->toBeNull()
            ->and($code->matrix()->errorCorrection)->toBe(ErrorCorrection::Quartile);

        config()->set('qr.defaults.style', 'wide');

        expect(Qr::make(BUILDER_URL)->toStyle()->margin)->toBe(6);
    });

    it('never changes a builder, so one can be kept and built on', function (): void {
        $brand = Qr::style()->paint('#1c1917')->margin(2);
        $wedding = $brand->paint('#be123c');

        expect($brand->toStyle()->modules->paint->toCss())->toBe('#1c1917')
            ->and($wedding->toStyle()->modules->paint->toCss())->toBe('#be123c')
            ->and($brand->for(BUILDER_URL)->toStyle()->margin)->toBe(2);
    });

    it('keeps a size, a version and a mask', function (): void {
        $matrix = Qr::make(BUILDER_URL)->minVersion(7)->mask(3)->matrix();

        expect($matrix->version)->toBe(7)
            ->and($matrix->mask)->toBe(Mask::Pattern3)
            ->and(Qr::make(BUILDER_URL)->mask(Mask::Pattern1)->matrix()->mask)->toBe(Mask::Pattern1)
            ->and(Qr::make(BUILDER_URL)->errorCorrection(ErrorCorrection::Low)->matrix()->errorCorrection)->toBe(ErrorCorrection::Low);
    });

    it('applies the config\'s scan safety, or the code\'s own', function (): void {
        config()->set('qr.scan_safety', ['min_margin' => 3, 'on_violation' => 'warn', 'min_contrast' => null]);

        expect(Qr::make(BUILDER_URL)->margin(0)->scene()->margin)->toBe(3)
            ->and(Qr::make(BUILDER_URL)->margin(0)->scanSafety(ScanSafety::off())->scene()->margin)->toBe(0)
            ->and(ScanSafety::fromConfig(['on_violation' => \ExeQue\Qr\Rendering\Enums\Violation::Warn])->onViolation)->toBe(\ExeQue\Qr\Rendering\Enums\Violation::Warn);
    });
});

describe('named settings', function (): void {
    it('takes error correction in either case', function (): void {
        expect(Qr::make(BUILDER_URL)->errorCorrection('q')->matrix()->errorCorrection)->toBe(ErrorCorrection::Quartile);
    });

    it('says which values a named setting takes', function (callable $build, string $message): void {
        expect($build)->toThrow(\ExeQue\Qr\Exceptions\InvalidOptionException::class, $message);
    })->with([
        [fn () => Qr::make(BUILDER_URL)->errorCorrection('x'), '"level" must be one of L, M, Q, H; "x" given.'],
        [fn () => Qr::make(BUILDER_URL)->mask(9), '"mask" must be one of 0, 1, 2, 3, 4, 5, 6, 7; "9" given.'],
        [fn () => Qr::make(BUILDER_URL)->logo(file: logoFixture(), shape: 'oval'), '"shape" must be one of square, rounded, circle, alpha; "oval" given.'],
        [fn () => Qr::make(BUILDER_URL)->logo(fn (LogoBuilder $logo) => $logo->file(logoFixture())->shape('oval')), '"shape" must be one of'],
        [fn () => Qr::make(BUILDER_URL)->png(driver: 'foo'), '"driver" must be one of auto, imagick, gd; "foo" given.'],
    ]);
});

describe('styling', function (): void {
    it('takes every form of shape, eye and filter', function (): void {
        $code = Qr::make(BUILDER_URL)
            ->modules(Module::Square)
            ->modules(Square::class)
            ->modules('square')
            ->modules(new Square())
            ->eyes(Eye::Square)
            ->eyes('square')
            ->eyes(outer: OuterEye::Square, inner: 'square')
            ->outer('square')
            ->inner(InnerEye::Square)
            ->filter(Filter::MixedOpacity)
            ->filter('mixed-size', min: 0.9)
            ->filter(MixedSize::class, min: 0.8)
            ->filter(new MixedSize(min: 0.95))
            ->filter(fn (ModuleContext $cell, ModuleStyle $style): ModuleStyle => $style, name: 'nothing');

        expect(array_map(fn ($filter): string => $filter->name(), $code->toStyle()->filters()))->toBe(['mixed-opacity', 'mixed-size', 'mixed-size', 'mixed-size', 'nothing']);
    });

    it('builds no class from a name that is not a shape, filter or style', function (): void {
        \Tests\Fixtures\ConstructionSpy::$built = 0;

        expect(fn () => Qr::style()->modules(\Tests\Fixtures\ConstructionSpy::class))->toThrow(ResolveException::class)
            ->and(fn () => Qr::style()->filter(\Tests\Fixtures\ConstructionSpy::class))->toThrow(ResolveException::class)
            ->and(\Tests\Fixtures\ConstructionSpy::$built)->toBe(0);
    });

    it('says what it could not make sense of', function (callable $build, string $message): void {
        expect($build)->toThrow(ResolveException::class, $message);
    })->with([
        [fn () => Qr::style()->modules('tulips'), '"tulips" is not a module shape'],
        [fn () => Qr::style()->eyes('star'), '"star" is not a whole eye (outer and inner)'],
        [fn () => Qr::style()->outer('nope'), '"nope" is not an outer eye'],
        [fn () => Qr::style()->inner('ring'), '"ring" is not an inner eye'],
        [fn () => Qr::style()->filter('sparkle'), '"sparkle" is not a filter'],
        [fn () => Qr::style()->style('missing'), '"missing" is not a style'],
        [fn () => Qr::style()->filter(new class () implements \ExeQue\Qr\Filters\Contracts\NamedFilter {
            public function name(): string
            {
                return 'odd';
            }

            public function options(): array
            {
                return [];
            }
        }), 'is not a module or eye filter'],
    ]);

    it('replaces and drops filters', function (): void {
        $code = Qr::style()->filter('jitter')->filters(['mixed-opacity', Filter::EyeTilt])->withoutFilter('eye-tilt');

        expect(array_map(fn ($filter): string => $filter->name(), $code->toStyle()->filters()))->toBe(['mixed-opacity']);
    });

    it('builds the parts with nested builders', function (): void {
        $code = Qr::make(BUILDER_URL)
            ->modules(fn (ModulesBuilder $modules): ModulesBuilder => $modules->shape('square')->paint('#1c1917')->opacity(0.9)->size(0.95)->rotate(5))
            ->eyes(fn (EyesBuilder $eyes): EyesBuilder => $eyes
                ->outer('square')
                ->inner(InnerEye::Square)
                ->paint('#1e1b4b')
                ->topRight(fn (EyeBuilder $eye): EyeBuilder => $eye->rotate(15)->paint('#be123c'))
                ->topLeft(fn (EyeBuilder $eye): EyeBuilder => $eye->outerPaint('#0f766e')->innerPaint('#7c3aed'))
                ->bottomLeft(fn (EyeBuilder $eye): EyeBuilder => $eye->whole('square'))
                ->mirrored())
            ->background(fn (BackgroundBuilder $background): BackgroundBuilder => $background->paint('#fff7ed'))
            ->eye(Corner::BottomLeft, rotation: 10, paint: '#be123c', outer: 'square', inner: 'square', eye: Eye::Square)
            ->eye(Corner::TopLeft, fn (EyeBuilder $eye): EyeBuilder => $eye->rotate(-3));

        $style = $code->toStyle();

        expect($style->modules->opacity)->toBe(0.9)
            ->and($style->eye(Corner::TopRight)->outer->rotation)->toBe(105.0)
            ->and($style->eye(Corner::TopRight)->outer->paint?->toCss())->toBe('#be123c')
            ->and($style->eye(Corner::TopLeft)->outer->paint?->toCss())->toBe('#0f766e')
            ->and($style->eye(Corner::TopLeft)->outer->rotation)->toBe(-3.0)
            ->and($style->eye(Corner::BottomLeft)->inner->rotation)->toBe(10.0)
            ->and($style->background?->toCss())->toBe('#fff7ed')
            ->and(Qr::style()->background(fn (BackgroundBuilder $background): BackgroundBuilder => $background->transparent())->toStyle()->background)->toBeNull()
            ->and(Qr::style()->background(null)->toStyle()->background)->toBeNull()
            ->and(Qr::style()->background(LinearGradient::make('#fff', '#eee'))->toStyle()->background)->toBeInstanceOf(LinearGradient::class);
    });

    it('merges styles in, later ones and later calls winning', function (): void {
        Qr::styles()->register('brand', ['modules' => ['paint' => '#1c1917'], 'margin' => 2]);

        $provider = new class () implements ProvidesQrStyle {
            public function qrStyle(): Style
            {
                return Style::default()->withMargin(3);
            }
        };

        $code = Qr::make(BUILDER_URL)
            ->style('brand', $provider, Qr::style()->mirrored(), fn (StyleBuilder $style): StyleBuilder => $style->paint('#be123c'))
            ->margin(5);

        expect($code->toStyle()->margin)->toBe(5)
            ->and($code->toStyle()->modules->paint->toCss())->toBe('#be123c')
            ->and($code->toStyle()->eye(Corner::TopRight)->outer->rotation)->toBe(90.0)
            ->and($code->toArray())->toBe($code->toStyle()->toArray());
    });

    it('takes options with an enum case, over its preset\'s', function (): void {
        expect(Qr::make(BUILDER_URL)->modules(\ExeQue\Qr\Shapes\Enums\Module::DotsMedium, diameter: 0.7)->toStyle()->modules->shape)
            ->toEqual(new \ExeQue\Qr\Shapes\Modules\Dot(0.7))
            ->and(Qr::make(BUILDER_URL)->outer(\ExeQue\Qr\Shapes\Enums\OuterEye::Octagon, cut: 1.5)->toStyle()->eye(\ExeQue\Qr\Rendering\Enums\Corner::TopLeft)->outer->shape)
            ->toEqual(new \ExeQue\Qr\Shapes\OuterEyes\Octagon(1.5));
    });

});

describe('macros', function (): void {
    it('reads conditions fluently, taps and takes macros', function (): void {
        PendingQr::macro('branded', fn (): PendingQr => $this->paint('#be123c'));
        $tapped = null;

        $code = Qr::make(BUILDER_URL)
            ->when(false)->margin(9)
            ->when(true)->margin(3)
            ->tap(function (PendingQr $code) use (&$tapped): void {
                $tapped = $code;
            })
            ->branded();

        expect($code->toStyle()->margin)->toBe(3)
            ->and($code->toStyle()->modules->paint->toCss())->toBe('#be123c')
            ->and($tapped)->toBeInstanceOf(PendingQr::class);
    });

    it('takes macros on the facade too', function (): void {
        QrManager::macro('brand', fn (string $url): PendingQr => $this->make($url)->paint('#be123c'));

        expect(Qr::brand(BUILDER_URL)->toStyle()->modules->paint->toCss())->toBe('#be123c');
    });
});

describe('logos', function (): void {
    it('takes a logo in every form, raising error correction to High', function (mixed $logo): void {
        $code = Qr::make(BUILDER_URL)->logo($logo, size: 0.2, shape: 'circle', plate: '#ffffff');

        expect($code->toStyle()->logo?->shape)->toBe(LogoShape::Circle)
            ->and($code->matrix()->errorCorrection)->toBe(ErrorCorrection::High)
            ->and($code->svg())->toDecodeAs(BUILDER_URL);
    })->with([
        'file' => [fn () => new SplFileInfo(logoFixture())],
        'data uri' => [fn () => Logo::fromFile(logoFixture())->toDataUri()],
        'svg' => [fn () => (string) file_get_contents(logoFixture())],
        'logo' => [fn () => Logo::fromFile(logoFixture())],
        'provider' => [fn () => new class () implements ProvidesQrLogo {
            public function qrLogo(): Logo
            {
                return Logo::fromFile(logoFixture());
            }
        }],
    ])->group('scan');

    it('takes a logo from a disk, with its builder', function (): void {
        Storage::fake('brands')->put('acme.svg', (string) file_get_contents(logoFixture()));

        $code = Qr::make(BUILDER_URL)->logo(fn (LogoBuilder $logo): LogoBuilder => $logo
            ->from('brands', 'acme.svg')
            ->size(0.18)
            ->padding(2)
            ->shape(LogoShape::Rounded)
            ->plate('#ffffff', radius: 1)
            ->position(14, 14));

        expect($code->toStyle()->logo?->padding)->toBe(2)
            ->and($code->toStyle()->logo?->position)->toBe([14.0, 14.0])
            ->and(Qr::make(BUILDER_URL)->logo(fn (LogoBuilder $logo): LogoBuilder => $logo->from(Storage::disk('brands'), 'acme.svg'))->toStyle()->logo)->not->toBeNull();
    });

    it('keeps the error correction it was given, logo or not', function (): void {
        config()->set('qr.logos.auto_error_correction', false);

        expect(Qr::make(BUILDER_URL)->errorCorrection('Q')->logo(file: logoFixture())->matrix()->errorCorrection)->toBe(ErrorCorrection::Quartile)
            ->and(Qr::make(BUILDER_URL)->logo(file: logoFixture())->matrix()->errorCorrection)->toBe(ErrorCorrection::Medium);
    });

    it('refuses a logo it cannot read', function (callable $build, string $message): void {
        expect($build)->toThrow(InvalidLogoException::class, $message);
    })->with([
        [fn () => new LogoBuilder()->toLogoStyle(), 'The logo has no image: give it one, or call from() or file() first.'],
        [fn () => new LogoBuilder()->from(null), 'A logo cannot be read from null'],
    ]);
})->group('logos');

describe('output', function (): void {
    it('writes every format', function (string $method, string $mime): void {
        $rendered = Qr::make(BUILDER_URL)->{$method}();

        expect($rendered->mimeType())->toBe($mime)
            ->and($rendered->output()->mimeType())->toBe($mime);
    })->with([
        ['svg', 'image/svg+xml'],
        ['png', 'image/png'],
        ['jpeg', 'image/jpeg'],
        ['webp', 'image/webp'],
    ]);

    it('passes writer options on, over the config\'s', function (): void {
        config()->set('qr.svg.precision', 1);
        config()->set('qr.raster.dpi', 300);
        config()->set('qr.defaults.size', 240);

        $image = new Imagick();
        $image->readImageBlob(Qr::make(BUILDER_URL)->png()->contents());

        expect(Qr::make(BUILDER_URL)->svg(size: 200, symbol: 'code')->contents())->toContain('<symbol id="code"')
            ->and($image->getImageGeometry())->toBe(['width' => 240, 'height' => 240])
            ->and(round($image->getImageResolution()['x'] * 2.54))->toBe(300.0);
    });

    it('writes attribution from the config and the code', function (): void {
        config()->set('qr.metadata', ['author' => 'Acme', 'copyright' => '© Acme']);

        $svg = Qr::make(BUILDER_URL)->metadata(title: 'Invite', keywords: ['wedding'])->metadata(author: 'Events team')->svg()->contents();

        expect($svg)->toContain('<title>Invite</title>')
            ->toContain('<rdf:li>Events team</rdf:li>')
            ->toContain('© Acme')
            ->toContain('<rdf:li>wedding</rdf:li>')
            ->and(Qr::make(BUILDER_URL)->metadata(new \ExeQue\Qr\Writers\Metadata(title: 'Given'))->svg()->contents())->toContain('<title>Given</title>');
    });

    it('saves to a disk, by name, instance or default, from a stream', function (): void {
        Storage::fake('codes');
        Storage::fake('local');
        $png = Qr::make(BUILDER_URL)->png(200);

        expect($png->save('qr/abc.png', 'codes'))->toBeTrue()
            ->and($png->store('qr/def.png', Storage::disk('codes')))->toBeTrue()
            ->and($png->save('qr/ghi.png'))->toBeTrue()
            ->and(Storage::disk('codes')->get('qr/abc.png'))->toBe($png->contents())
            ->and(Storage::disk('codes')->exists('qr/def.png'))->toBeTrue()
            ->and(Storage::disk('local')->exists('qr/ghi.png'))->toBeTrue();
    });

    it('sends itself as a download or as the response', function (): void {
        $svg = Qr::make(BUILDER_URL)->svg();

        $download = $svg->download('invite.svg');
        ob_start();
        $download->sendContent();
        $streamed = ob_get_clean();

        $response = $svg->toResponse(new Request());

        expect($download->headers->get('Content-Disposition'))->toBe('attachment; filename=invite.svg')
            ->and($download->headers->get('Content-Type'))->toBe('image/svg+xml')
            ->and($streamed)->toBe($svg->contents())
            ->and(Qr::make(BUILDER_URL)->png()->download()->headers->get('Content-Disposition'))->toBe('attachment; filename=qr.png')
            ->and(Qr::make(BUILDER_URL)->png()->download('../kodé "1".png')->headers->get('Content-Disposition'))
            ->toBe('attachment; filename=".._kod_ \\"1\\".png"; filename*=utf-8\'\'.._kod%C3%A9%20%221%22.png')
            ->and(Qr::make(BUILDER_URL)->png()->download('ø')->headers->get('Content-Disposition'))->toStartWith('attachment; filename=_;')
            ->and($response->headers->get('Content-Type'))->toBe('image/svg+xml')
            ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
            ->and($response->headers->get('Content-Security-Policy'))->toContain("default-src 'none'")
            ->and(Qr::make(BUILDER_URL)->png()->toResponse(new Request())->headers->has('Content-Security-Policy'))->toBeFalse()
            ->and($response->getContent())->toBe($svg->contents());
    });

    it('encodes itself every way', function (): void {
        $png = Qr::make(BUILDER_URL)->png(100);
        $contents = $png->contents();
        $stream = $png->toResource();

        expect($png->toBase64())->toBe(base64_encode($contents))
            ->and(base64_decode(strtr($png->toBase64Url(), '-_', '+/')))->toBe($contents)
            ->and($png->toBase64Url())->not->toContain('=')->not->toContain('+')->not->toContain('/')
            ->and($png->toDataUri())->toBe('data:image/png;base64,'.base64_encode($contents))
            ->and(implode(iterator_to_array($png->chunks(512), false)))->toBe($contents)
            ->and(stream_get_contents($stream))->toBe($contents)
            ->and((string) $png->toStream())->toBe($contents)
            ->and($png->toStream()->tell())->toBe(0)
            ->and($png->extension())->toBe('png')
            ->and((string) $png)->toBe($contents);

        fclose($stream);
        $handle = $png->detach();
        expect(stream_get_contents($handle))->toBe($contents);
        fclose($handle);
    });

    it('embeds in Blade: SVG inline, images as an img', function (): void {
        $code = Qr::make(BUILDER_URL);

        expect($code->toHtml())->toStartWith('<svg')
            ->and((string) $code)->toBe($code->toHtml())
            ->and($code->svg()->toHtml())->toStartWith('<svg')
            ->and($code->png(100)->toHtml())->toStartWith('<img src="data:image/png;base64,');
    });
});
