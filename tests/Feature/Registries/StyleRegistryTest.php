<?php

declare(strict_types=1);

namespace Tests\Feature\Registries;

use ExeQue\Qr\Colors\Hex;
use ExeQue\Qr\Exceptions\InvalidStyleException;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Filters\EyeTilt;
use ExeQue\Qr\Filters\MixedOpacity;
use ExeQue\Qr\Logos\Logo;
use ExeQue\Qr\Logos\LogoStyle;
use ExeQue\Qr\Registries\StyleRegistry;
use ExeQue\Qr\Rendering\Contracts\ProvidesQrStyle;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Rendering\StyleSerializer;

covers(\ExeQue\Qr\Qr::class, \ExeQue\Qr\Exceptions\RegistryException::class, StyleRegistry::class, StyleSerializer::class, Style::class, InvalidStyleException::class);

pest()->group('registries');

enum EventStyle: string implements ProvidesQrStyle
{
    case Wedding = 'wedding';
    case Christening = 'christening';

    public function qrStyle(): Style
    {
        return Style::default()->withPaint(new Hex($this === self::Wedding ? '#be123c' : '#0f766e'));
    }
}

final class PrintStyle implements ProvidesQrStyle
{
    public function __construct(
        private int $margin = 4,
    ) {
    }

    public function qrStyle(): Style
    {
        return Style::default()->withMargin($this->margin);
    }
}

function paintOf(Style $style): string
{
    return $style->modules->paint->toCss();
}

describe('registering', function (): void {
    it('takes every form', function (mixed $value, string $paint): void {
        Qr::styles()->register('brand', $value);

        expect(paintOf(Qr::styles()->get('brand')))->toBe($paint);
    })->with([
        'style' => [fn () => Style::default()->withPaint(new Hex('#1c1917')), '#1c1917'],
        'provider' => [EventStyle::Wedding, '#be123c'],
        'stored array' => [['modules' => ['paint' => '#0f766e']], '#0f766e'],
    ]);

    it('builds a style class through the container, with options', function (): void {
        Qr::styles()->register('print', PrintStyle::class);

        expect(Qr::styles()->get('print')->margin)->toBe(4)
            ->and(Qr::styles()->get('print', ['margin' => 6])->margin)->toBe(6);
    });

    it('calls a closure for its style', function (): void {
        Qr::styles()->register('lazy', fn (): Style => Style::default()->withMargin(3));

        expect(Qr::styles()->get('lazy')->margin)->toBe(3);
    });

    it('registers every case of a style enum', function (): void {
        Qr::styles()->register(EventStyle::class);

        expect(paintOf(Qr::styles()->get('christening')))->toBe('#0f766e')
            ->and(Qr::styles()->names())->toBe(['wedding', 'christening']);
    });

    it('takes styles from the config', function (): void {
        config()->set('qr.styles', ['brand' => ['margin' => 2], EventStyle::class]);

        expect(Qr::styles()->get('brand')->margin)->toBe(2)
            ->and(paintOf(Qr::styles()->get('wedding')))->toBe('#be123c');
    });

    it('says which styles it has when one is unknown', function (): void {
        Qr::styles()->register('brand', Style::default());

        Qr::styles()->get('wedding');
    })->throws(\ExeQue\Qr\Exceptions\RegistryException::class, 'No style is registered as "wedding"; registered are: brand.');

    it('takes a style builder, or a closure that gives one', function (): void {
        Qr::styles()->register('built', Qr::style()->margin(2));
        Qr::styles()->register('lazy', fn () => Qr::style()->margin(3));

        expect(Qr::styles()->get('built')->margin)->toBe(2)
            ->and(Qr::styles()->get('lazy')->margin)->toBe(3);
    });

});

describe('extending', function (): void {
    it('builds on the styles it extends, later ones winning', function (): void {
        Qr::styles()->register('brand', ['modules' => ['paint' => '#1c1917'], 'margin' => 2]);
        Qr::styles()->register('soft', ['modules' => ['opacity' => 0.9], 'margin' => 3]);
        Qr::styles()->register('wedding', ['extends' => ['brand', 'soft'], 'modules' => ['paint' => '#be123c']]);
        Qr::styles()->register('late-wedding', ['extends' => 'wedding', 'transparent' => true]);

        $style = Qr::styles()->get('late-wedding');

        expect(paintOf($style))->toBe('#be123c')
            ->and($style->modules->opacity)->toBe(0.9)
            ->and($style->margin)->toBe(3)
            ->and($style->background)->toBeNull();
    });

    it('refuses styles that extend each other in a circle', function (): void {
        Qr::styles()->register('a', ['extends' => 'b']);
        Qr::styles()->register('b', ['extends' => 'c']);
        Qr::styles()->register('c', ['extends' => 'a']);

        Qr::styles()->get('a');
    })->throws(InvalidStyleException::class, 'The styles extend each other in a circle: a extends b extends c extends a.');

    it('works a style out once, and again when the styles change', function (): void {
        $calls = 0;
        Qr::styles()->register('counted', function () use (&$calls): Style {
            $calls++;

            return Style::default();
        });

        Qr::styles()->get('counted');
        Qr::styles()->get('counted');
        Qr::styles()->register('other', Style::default());
        Qr::styles()->get('counted');
        Qr::styles()->forget('other');
        Qr::styles()->get('counted');

        expect($calls)->toBe(3);
    });

    it('merges part by part', function (): void {
        $eye = Style::default()->eye(Corner::TopLeft);
        $base = Style::default()
            ->withPaint(new Hex('#1c1917'))
            ->withEye(Corner::TopRight, $eye->withOuter($eye->outer->withPaint(new Hex('#be123c')))->withInner($eye->inner->withRotation(10)))
            ->withFilter(new MixedOpacity())
            ->withLogo(new LogoStyle(Logo::fromFile(dirname(__DIR__, 2).'/Fixtures/logos/badge.svg')));
        $over = Style::default()
            ->withEye(Corner::TopRight, $eye->withInner($eye->inner->withOpacity(0.5)))
            ->withFilter(new EyeTilt())
            ->withBackground(null);

        $merged = $base->merge($over);

        expect(paintOf($merged))->toBe('#1c1917')
            ->and($merged->eye(Corner::TopRight)->outer->paint?->toCss())->toBe('#be123c')
            ->and($merged->eye(Corner::TopRight)->inner->rotation)->toBe(10.0)
            ->and($merged->eye(Corner::TopRight)->inner->opacity)->toBe(0.5)
            ->and(array_map(fn ($filter): string => $filter->name(), $merged->filters()))->toBe(['mixed-opacity', 'eye-tilt'])
            ->and($merged->background)->toBeNull()
            ->and($merged->logo)->toBe($base->logo)
            ->and(Style::default()->withBackground(null)->merge(Style::default()->withBackground(new Hex('#fff7ed')))->background?->toCss())->toBe('#fff7ed');
    });
});
