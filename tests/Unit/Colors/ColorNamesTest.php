<?php

declare(strict_types=1);

namespace Tests\Unit\Colors;

use ExeQue\Qr\Colors\Color;
use ExeQue\Qr\Colors\ColorNames;
use ExeQue\Qr\Colors\Named;
use ExeQue\Qr\Exceptions\InvalidColorException;
use Illuminate\Container\Container;

covers(ColorNames::class, Named::class, InvalidColorException::class);

pest()->group('styling');

beforeEach(function (): void {
    $this->names = new ColorNames();
    Container::setInstance(new Container())->instance(ColorNames::class, $this->names);
});

afterEach(fn () => Container::setInstance(null));

describe('color names', function (): void {
    it('uses an application name wherever a color goes', function (): void {
        $this->names->register('Brand', 'oklch(55% 0.24 293)');

        $color = Color::parse('brand');

        expect($color)->toBeInstanceOf(Named::class)
            // Only the application knows its names, so CSS gets the color itself.
            ->and($color->toCss())->toBe('rgb(126 65 237)')
            ->and($color->toHex())->toBe(Color::parse('oklch(55% 0.24 293)')->toHex())
            ->and(Named::from(Color::parse('oklch(55% 0.24 293)'))->name)->toBe('brand');
    });

    it('takes names when made', function (): void {
        $names = new ColorNames(['brand' => '#be123c', 'accent' => '#7c3aed']);

        expect(array_keys($names->all()))->toBe(['brand', 'accent'])
            ->and($names->get('BRAND')?->toHex())->toBe('#be123c');
    });

    it('resolves a name given as the value straight away', function (): void {
        $this->names->register('brand', '#be123c');
        $this->names->register('accent', 'brand');
        $this->names->register('brand', '#000000');

        expect(Color::parse('accent')->toHex())->toBe('#be123c');
    });

    it('keeps the color a name had when the color was made', function (): void {
        $this->names->register('brand', '#be123c');
        $color = Color::parse('brand');
        $this->names->register('brand', '#000000');

        expect($color->toHex())->toBe('#be123c');
    });

    it('forgets a name', function (): void {
        $this->names->register('brand', '#be123c');
        $this->names->forget('BRAND');

        expect($this->names->has('brand'))->toBeFalse();
    });

    it('refuses names that are not names', function (string $name, string $message): void {
        expect(fn () => $this->names->register($name, '#000'))->toThrow(InvalidColorException::class, $message);
    })->with([
        ['red', '"red" is a CSS color name and cannot be redefined.'],
        ['currentColor', '"currentcolor" is a CSS color name and cannot be redefined.'],
        ['9lives', '"9lives" cannot name a color: use letters, digits and dashes, starting with a letter.'],
        ['brand color', '"brand color" cannot name a color: use letters, digits and dashes, starting with a letter.'],
    ]);

    it('keeps the alpha of an application name', function (): void {
        $this->names->register('overlay', 'rgb(0 0 0 / 50%)');

        expect(Named::make('overlay')->alpha())->toBe(0.5)
            ->and(Named::make('overlay', 0.2)->alpha())->toBe(0.2)
            ->and(Named::make('overlay')->toCss())->toBe('rgb(0 0 0 / 0.5)');
    });
});
