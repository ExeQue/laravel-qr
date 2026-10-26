<?php

declare(strict_types=1);

namespace Tests\Feature\Validation;

use ExeQue\Qr\Colors\Hex;
use ExeQue\Qr\Exceptions\InvalidStyleException;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Filters\ClosureFilter;
use ExeQue\Qr\Filters\MixedShape;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Logos\Enums\LogoShape;
use ExeQue\Qr\Logos\Logo;
use ExeQue\Qr\Logos\LogoStyle;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;
use ExeQue\Qr\Shapes\Enums\Eye;
use ExeQue\Qr\Shapes\Enums\Module;
use ExeQue\Qr\Shapes\Modules\Rounded;
use ExeQue\Qr\Validation\InteractsWithQrStyle;
use ExeQue\Qr\Validation\StyleInput;
use ExeQue\Qr\Validation\StyleRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Livewire\Form;
use RuntimeException;
use SplFileInfo;

covers(StyleInput::class, StyleRule::class, InteractsWithQrStyle::class, InvalidStyleException::class);

pest()->group('validation');

const LOGO_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><circle cx="5" cy="5" r="5" fill="#be123c"/></svg>';

describe('to a style', function (): void {
    it('applies every field over the base', function (): void {
        $style = StyleRule::make()->toStyle([
            'modules' => ['shape' => 'rounded', 'options' => ['radius' => 0.1], 'paint' => '#1e1b4b', 'rotation' => 10, 'scaleX' => 0.9, 'opacity' => 0.8],
            'eyes' => [
                'preset' => 'leaf',
                'paint' => '#be123c',
                'corners' => ['top-right' => ['outer' => ['shape' => 'circle', 'paint' => '#0f766e', 'rotation' => 5, 'scaleY' => 0.95, 'opacity' => 0.9], 'inner' => ['shape' => 'soft']]],
            ],
            'background' => '#fef3c7',
            'margin' => 3,
            'filters' => [['name' => 'mixed-rotation', 'options' => ['min' => -5, 'max' => 5]]],
        ]);
        $topRight = $style->eye(Corner::TopRight);

        expect($style->modules->shape)->toEqual(new Rounded(radius: 0.1))
            ->and([$style->modules->rotation, $style->modules->scaleX, $style->modules->scaleY, $style->modules->opacity])->toBe([10.0, 0.9, 1.0, 0.8])
            ->and($style->modules->paint->toCss())->toBe('#1e1b4b')
            ->and($style->eye(Corner::TopLeft)->outer->shape)->toEqual(Eye::Leaf->qrOuterEye())
            ->and($style->eye(Corner::TopLeft)->outer->paint?->toCss())->toBe('#be123c')
            ->and($topRight->outer->shape)->toEqual(Qr::outerEyes()->get('circle'))
            ->and($topRight->outer->paint?->toCss())->toBe('#0f766e')
            ->and([$topRight->outer->rotation, $topRight->outer->scaleX, $topRight->outer->scaleY, $topRight->outer->opacity])->toBe([5.0, 1.0, 0.95, 0.9])
            ->and($topRight->inner->paint?->toCss())->toBe('#be123c')
            ->and($style->background?->toCss())->toBe('#fef3c7')
            ->and($style->margin)->toBe(3)
            ->and($style->filters()[0]->name())->toBe('mixed-rotation');
    });

    it('starts from the base given, by style or name, and takes a transparent background', function (): void {
        Qr::styles()->register('dark', Style::default()->withMargin(6));

        expect(StyleRule::make()->toStyle([], base: 'dark')->margin)->toBe(6)
            ->and(StyleRule::make()->toStyle(['background' => null], base: Style::default()->withMargin(5))->background)->toBeNull()
            ->and(StyleRule::make()->toStyle(['background' => null], base: Style::default()->withMargin(5))->margin)->toBe(5);
    });

    it('takes a logo\'s settings with the image given apart, or the base\'s', function (): void {
        $file = tempnam(sys_get_temp_dir(), 'logo').'.svg';
        file_put_contents($file, LOGO_SVG);
        $settings = ['logo' => ['size' => 'auto', 'padding' => 2, 'shape' => 'circle', 'plate' => '#ffffff', 'plateRadius' => 1, 'position' => [3, 4]]];

        $style = StyleRule::make()->toStyle($settings, logo: new SplFileInfo($file));
        $kept = StyleRule::make()->toStyle(['logo' => ['plateRadius' => 2, 'position' => null, 'plate' => null]], base: $style);
        $radiusOnly = StyleRule::make()->toStyle(['logo' => ['plateRadius' => 3]], base: $style);

        expect($style->logo?->size)->toBeNull()
            ->and([$style->logo?->padding, $style->logo?->shape, $style->logo?->plate?->toCss(), $style->logo?->plateRadius, $style->logo?->position])->toBe([2, LogoShape::Circle, '#ffffff', 1.0, [3.0, 4.0]])
            ->and([$kept->logo?->plate, $kept->logo?->plateRadius, $kept->logo?->position])->toBe([null, 2.0, null])
            ->and([$radiusOnly->logo?->plate?->toCss(), $radiusOnly->logo?->plateRadius])->toBe(['#ffffff', 3.0])
            ->and(StyleRule::make()->toStyle(['logo' => ['size' => 0.1]], logo: Logo::fromSvg(LOGO_SVG))->logo?->size)->toBe(0.1)
            ->and(fn () => StyleRule::make()->toStyle(['logo' => ['size' => 0.1]]))->toThrow(InvalidStyleException::class, 'A stored logo has no source');

        unlink($file);
    });
});

describe('to input', function (): void {
    it('leaves a default style empty', function (): void {
        expect(StyleRule::toPayload(Style::default()))->toBe([]);
    });

    it('writes eyes once when every corner is alike, a preset where one fits', function (): void {
        $style = Qr::style()->eyes(Eye::Leaf)->eyes(fn ($eyes) => $eyes->paint('#1e1b4b'))->toStyle();

        expect(StyleRule::toPayload($style)['eyes'])->toBe(['preset' => 'leaf', 'paint' => '#1e1b4b']);
    });

    it('writes eyes by corner, parts where no preset fits', function (): void {
        $style = Qr::style()->eyes(Eye::Leaf)->eye(Corner::TopLeft, outer: 'circle', rotate: 10)->eye(Corner::TopRight, outer: 'circle')->eye(Corner::BottomLeft, paint: '#be123c')->toStyle();
        $style = $style->withEye(Corner::TopRight, $style->eye(Corner::TopRight)->withOuter($style->eye(Corner::TopRight)->outer->withSize(0.95)->withOpacity(0.9)->withPaint(new Hex('#0f766e'))));

        expect(StyleRule::toPayload($style)['eyes'])->toBe(['corners' => [
            'top-left' => ['outer' => ['shape' => 'circle', 'rotation' => 10.0], 'inner' => ['shape' => 'leaf', 'rotation' => 10.0]],
            'top-right' => ['outer' => ['shape' => 'circle', 'paint' => '#0f766e', 'scaleX' => 0.95, 'scaleY' => 0.95, 'opacity' => 0.9], 'inner' => ['shape' => 'leaf']],
            'bottom-left' => ['preset' => 'leaf', 'paint' => '#be123c'],
        ]]);
    });

    it('writes modules, background, margin, filters and logo, and reads back as the same style', function (): void {
        $style = Qr::style()
            ->modules(Module::DotsMedium)
            ->paint('#1e1b4b')
            ->background(null)
            ->margin(3)
            ->filter('mixed-rotation', min: -5, max: 5)
            ->filter(new MixedShape([Module::Square, Module::DotsMedium, new Rounded(radius: 0.1)]))
            ->toStyle();
        $style = $style->withModules($style->modules->withRotate(10)->withSize(0.9, 0.8)->withOpacity(0.7));
        $payload = StyleRule::toPayload($style);

        expect($payload['modules'])->toBe(['shape' => 'dots-medium', 'paint' => '#1e1b4b', 'rotation' => 10.0, 'scaleX' => 0.9, 'scaleY' => 0.8, 'opacity' => 0.7])
            ->and($payload['background'])->toBeNull()
            ->and($payload['margin'])->toBe(3)
            ->and($payload['filters'][1]['options']['shapes'])->toBe(['square', 'dots-medium', ['class' => Rounded::class, 'options' => ['side' => 0.92, 'radius' => 0.1]]])
            ->and(StyleRule::make()->toStyle(array_diff_key($payload, ['filters' => true]))->toArray())->toBe($style->withFilters()->toArray());
    });

    it('writes input the rules take, which reads back as the style', function (): void {
        $style = Qr::style()->modules(Module::Hearts)->eyes(Eye::Leaf)->eye(Corner::TopLeft, outer: 'circle')->background('#fef3c7')->margin(3)->filter('wave')->toStyle();
        $payload = StyleRule::toPayload($style);

        expect(\Illuminate\Support\Facades\Validator::make(['style' => $payload], StyleRule::everything()->for('style'))->errors()->toArray())->toBe([])
            ->and(StyleRule::make()->toStyle($payload)->toArray())->toBe($style->toArray());
    });

    it('writes a shape with options of its own by its class\'s name', function (): void {
        $style = Style::default()->withModules(Style::default()->modules->withShape(new Rounded(side: 0.5)));

        expect(StyleRule::toPayload($style)['modules'])->toBe(['shape' => Qr::modules()->nameOf(Rounded::class), 'options' => ['side' => 0.5, 'radius' => 0.3]]);
    });

    it('writes parts that no preset pairs', function (): void {
        expect(StyleRule::toPayload(Qr::style()->outer('circle')->inner('star')->toStyle())['eyes'])->toBe(['outer' => ['shape' => 'circle'], 'inner' => ['shape' => 'star']]);
    });

    it('writes the logo\'s settings, not its image', function (): void {
        $style = Style::default()->withLogo(new LogoStyle(Logo::fromSvg(LOGO_SVG), null, 2, LogoShape::Circle, new Hex('#ffffff'), 1, [3, 4]));

        expect(StyleRule::toPayload($style)['logo'])->toBe(['size' => 'auto', 'padding' => 2, 'shape' => 'circle', 'plate' => '#ffffff', 'plateRadius' => 1.0, 'position' => [3, 4]]);
    });

    it('refuses what input cannot name', function (Style $style, string $message): void {
        expect(fn () => StyleRule::toPayload($style))->toThrow(InvalidStyleException::class, $message);
    })->with([
        'an unregistered shape' => [fn () => Style::default()->withModules(Style::default()->modules->withShape(new class () implements ModuleShape {
            public function draw(ModuleContext $cell): Path
            {
                return new Path();
            }
        })), "The style's module shape has no registered name"],
        'a closure filter' => [fn () => Style::default()->withFilters(new ClosureFilter(fn (ModuleContext $cell, ModuleStyle $style): ModuleStyle => $style, 'striped')), "The style's filter striped has no registered name"],
    ]);

    it('passes over names whose shapes cannot be made', function (): void {
        Qr::modules()->register('failing', fn (): never => throw new RuntimeException('No.'));

        expect(StyleRule::toPayload(Qr::style()->modules(new Rounded(side: 0.5))->toStyle())['modules']['options'])->toBe(['side' => 0.5, 'radius' => 0.3]);
    });
});

describe('from a form', function (): void {
    it('gives a form request\'s validated style', function (): void {
        $request = new class () extends FormRequest {
            use InteractsWithQrStyle;

            public function rules(): array
            {
                return StyleRule::everything()->for('style');
            }
        };
        $request->setContainer(app())->replace(['style' => ['margin' => 5]]);
        $request->validateResolved();

        expect($request->qrStyle()->margin)->toBe(5)
            ->and($request->qrStyle(base: Style::default()->withMargin(9), logo: Logo::fromSvg(LOGO_SVG))->margin)->toBe(5);
    });

    it('gives a Livewire form\'s validated style', function (): void {
        app()->register(\Livewire\LivewireServiceProvider::class);
        $component = new class () extends \Livewire\Component {
            public function render(): string
            {
                return '<div></div>';
            }
        };
        $form = new class ($component, 'form') extends Form {
            use InteractsWithQrStyle;

            /** @var array<string, mixed> */
            public array $style = ['margin' => 7];

            protected function rules(): array
            {
                return StyleRule::everything()->for('style');
            }
        };

        expect($form->qrStyle()->margin)->toBe(7);
    })->skip(! class_exists(Form::class), 'Livewire is not installed.');

    it('takes an uploaded logo', function (): void {
        $upload = UploadedFile::fake()->createWithContent('logo.svg', LOGO_SVG);

        expect(StyleRule::make()->toStyle(['logo' => ['size' => 0.2]], logo: $upload)->logo?->size)->toBe(0.2);
    });
});
