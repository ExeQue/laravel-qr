<?php

declare(strict_types=1);

namespace Tests\Unit\Rendering;

use ExeQue\Qr\Colors\Hex;
use ExeQue\Qr\Encoder\Encoder;
use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Exceptions\InvalidStyleException;
use ExeQue\Qr\Gradients\LinearGradient;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\Enums\Part;
use ExeQue\Qr\Rendering\EyePartStyle;
use ExeQue\Qr\Rendering\EyeStyle;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Rendering\Renderer;
use ExeQue\Qr\Rendering\ScanSafety;
use ExeQue\Qr\Rendering\Scene;
use ExeQue\Qr\Rendering\SceneItem;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Shapes\Enums\Eye;
use ExeQue\Qr\Shapes\Enums\InnerEye;
use ExeQue\Qr\Shapes\Enums\Module;
use ExeQue\Qr\Shapes\Enums\OuterEye;
use ExeQue\Qr\Shapes\InnerEyes\Square as SquareInner;
use ExeQue\Qr\Shapes\Modules\Square as SquareModule;
use ExeQue\Qr\Shapes\OuterEyes\Square as SquareOuter;

covers(
    Renderer::class,
    Scene::class,
    SceneItem::class,
    Style::class,
    ModuleStyle::class,
    EyeStyle::class,
    EyePartStyle::class,
    ModuleContext::class,
    Corner::class,
    Module::class,
    OuterEye::class,
    InnerEye::class,
    Eye::class,
    SquareModule::class,
    SquareOuter::class,
    SquareInner::class,
    InvalidStyleException::class,
);

pest()->group('rendering');

const FIXTURE_URL = 'https://example.com/e/ABCD1234';

function scene(?Style $style = null): Scene
{
    return new Renderer()->render(new Encoder()->encode(FIXTURE_URL), $style ?? Style::default());
}

describe('layout', function (): void {
    it('draws three eyes and every dark cell outside them', function (): void {
        $matrix = new Encoder()->encode(FIXTURE_URL);
        $items = scene()->items;
        $dark = 0;

        for ($y = 0; $y < $matrix->size; $y++) {
            for ($x = 0; $x < $matrix->size; $x++) {
                $dark += $matrix->isDark($x, $y) && $matrix->role($x, $y) !== Role::Finder ? 1 : 0;
            }
        }

        expect(array_filter($items, fn (SceneItem $item): bool => $item->part === Part::Module))->toHaveCount($dark)
            ->and(array_filter($items, fn (SceneItem $item): bool => $item->part === Part::OuterEye))->toHaveCount(3)
            ->and(array_filter($items, fn (SceneItem $item): bool => $item->part === Part::InnerEye))->toHaveCount(3);
    });

    it('places each shape on the canvas', function (): void {
        $eye = scene()->items[0];

        expect($eye->path()->bounds())->toBe([4.0, 4.0, 11.0, 11.0]);
    });

    it('places the canvas with its quiet zone', function (): void {
        $scene = scene(Style::default()->withMargin(2));

        expect($scene->size)->toBe(33)
            ->and($scene->margin)->toBe(2)
            ->and($scene->items[0]->center)->toBe([5.5, 5.5]);
    });

    it('refuses a negative margin', function (): void {
        Style::default()->withMargin(-1);
    })->throws(InvalidStyleException::class, 'The margin is at least 0 modules, -1 given.');
});

describe('eyes', function (): void {
    it('does not turn eyes unless asked', function (): void {
        foreach (array_slice(scene()->items, 0, 6) as $item) {
            expect($item->transform->toString())->toStartWith('translate(');
        }
    });

    it('turns the top-right and bottom-left eyes outwards when mirrored', function (): void {
        $style = Style::default()->mirrored();

        expect($style->eye(Corner::TopLeft)->outer->rotation)->toBe(0.0)
            ->and($style->eye(Corner::TopRight)->outer->rotation)->toBe(90.0)
            ->and($style->eye(Corner::TopRight)->inner->rotation)->toBe(90.0)
            ->and($style->eye(Corner::BottomLeft)->outer->rotation)->toBe(-90.0);
    });

    it('gives eyes their own paint, or the modules\' paint', function (): void {
        $red = new Hex('#be123c');
        $style = Style::default()
            ->withPaint(new Hex('#1c1917'))
            ->withEye(Corner::TopRight, Style::default()->eye(Corner::TopRight)->withPaint($red));
        $items = scene($style)->items;

        expect($items[0]->paint->toCss())->toBe('#1c1917')
            ->and($items[0]->inheritsPaint)->toBeTrue()
            ->and($items[2]->paint)->toBe($red)
            ->and($items[2]->inheritsPaint)->toBeFalse();
    });

    it('styles each eye part on its own', function (): void {
        $eye = EyeStyle::of(new SquareOuter(), new SquareInner())
            ->withOuter(new EyePartStyle(new SquareOuter(), rotation: 4))
            ->withInner(new EyePartStyle(new SquareInner())->withSize(0.8)->withRotation(-7)->withOpacity(0.5)->withShape(new SquareInner()));

        expect($eye->outer->transform()->toString())->toBe('rotate(4)')
            ->and($eye->inner->transform()->toString())->toBe('scale(0.8) rotate(-7)')
            ->and($eye->inner->opacity)->toBe(0.5)
            ->and(Style::default()->withEyes($eye)->eye(Corner::BottomLeft))->toBe($eye);
    });
});

describe('modules', function (): void {
    it('applies a module style\'s size, skew, rotation and offset about the cell', function (): void {
        $module = Style::default()->modules->withSize(0.5, 0.25)->withSkew(10, 5)->withRotation(30)->withOffset(0.1, -0.1);

        expect($module->transform()->toString())->toBe('scale(0.5 0.25) skew(10 5) rotate(30) translate(0.1 -0.1)');
    });

    it('skips modules and fades them', function (): void {
        $skipped = new Renderer()->render(new Encoder()->encode(FIXTURE_URL), Style::default()->withModules(Style::default()->modules->withSkip()), ScanSafety::off());
        $faded = scene(Style::default()->withModules(Style::default()->modules->withOpacity(0.5)));

        expect(array_filter($skipped->items, fn (SceneItem $item): bool => $item->part === Part::Module))->toBe([])
            ->and($faded->items[6]->opacity)->toBe(0.5);
    });

    it('changes a module style on copies', function (): void {
        $module = new ModuleStyle(new SquareModule(), new Hex('#000'));
        $changed = $module->withShape(new SquareModule())->withPaint(new Hex('#fff'));

        expect($module->paint->toCss())->toBe('#000000')
            ->and($changed->paint->toCss())->toBe('#ffffff');
    });

    it('tells a module about its neighbors, with finder cells as light', function (): void {
        $matrix = new Encoder()->encode(FIXTURE_URL);

        // (7, 7) is the separator corner next to the top-left finder; (8, 8) is format information.
        $cell = ModuleContext::of($matrix, 8, 6);

        expect($cell->isDark(0, 0))->toBeTrue()
            ->and($cell->isDark(-1, 0))->toBe(false)
            ->and($cell->x)->toBe(8.5)
            ->and($cell->role)->toBe(Role::Timing)
            ->and($cell->seed)->toBe(ModuleContext::of($matrix, 8, 6)->seed)
            ->and($cell->seed)->not->toBe(ModuleContext::of($matrix, 10, 6)->seed)
            ->and(ModuleContext::of($matrix, 6, 8)->isDark(0, -2))->toBeFalse();
    });

    it('reports a finder cell beside a module as light', function (): void {
        $matrix = new Encoder()->encode(FIXTURE_URL);

        // The timing cell at (6, 8) sits right below the top-left finder's bottom ring cell (6, 6) - two up -
        // and next to the separator; any finder cell it touches must read as light.
        $context = ModuleContext::of($matrix, 6, 8);

        expect($context->isDark(0, -1))->toBeFalse();
    });
});

describe('scenes', function (): void {
    it('describes paints of other kinds by class', function (): void {
        $paint = new class () implements \ExeQue\Qr\Contracts\Paint {};

        expect(Scene::describe($paint))->toBe($paint::class);
    });

    it('renders the same code identically every time', function (): void {
        expect(scene()->toSnapshot())->toBe(scene()->toSnapshot());
    });


    it('describes the scene for snapshots', function (): void {
        $style = Style::default()->mirrored()->withPaint(LinearGradient::make('#be123c', '#7c3aed')->angle(45))->withBackground(null);

        expect(scene($style))->toMatchSnapshot();
    });

    it('snapshots the default scene', function (): void {
        expect(scene())->toMatchSnapshot();
    });
});
