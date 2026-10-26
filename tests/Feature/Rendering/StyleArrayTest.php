<?php

declare(strict_types=1);

namespace Tests\Feature\Rendering;

use ExeQue\Qr\Colors\Hex;
use ExeQue\Qr\Encoder\Encoder;
use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Exceptions\InvalidStyleException;
use ExeQue\Qr\Exceptions\RegistryException;
use ExeQue\Qr\Filters\ByRole;
use ExeQue\Qr\Filters\Compound;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Filters\EyePaint;
use ExeQue\Qr\Filters\EyeSize;
use ExeQue\Qr\Filters\EyeTilt;
use ExeQue\Qr\Filters\GradientFill;
use ExeQue\Qr\Filters\InRegion;
use ExeQue\Qr\Filters\Jitter;
use ExeQue\Qr\Filters\MixedOffset;
use ExeQue\Qr\Filters\MixedOpacity;
use ExeQue\Qr\Filters\MixedRotation;
use ExeQue\Qr\Filters\MixedShape;
use ExeQue\Qr\Filters\MixedSize;
use ExeQue\Qr\Filters\MixedSkew;
use ExeQue\Qr\Filters\Palette;
use ExeQue\Qr\Filters\Radial;
use ExeQue\Qr\Filters\Region;
use ExeQue\Qr\Filters\Wave;
use ExeQue\Qr\Gradients\ConicGradient;
use ExeQue\Qr\Gradients\Contracts\Gradient;
use ExeQue\Qr\Gradients\DiamondGradient;
use ExeQue\Qr\Gradients\Enums\ColorSpace;
use ExeQue\Qr\Gradients\Enums\GradientBox;
use ExeQue\Qr\Gradients\Enums\HueInterpolation;
use ExeQue\Qr\Gradients\Enums\Sampling;
use ExeQue\Qr\Gradients\Enums\Spread;
use ExeQue\Qr\Gradients\LinearGradient;
use ExeQue\Qr\Gradients\MeshGradient;
use ExeQue\Qr\Gradients\RadialGradient;
use ExeQue\Qr\Logos\Enums\LogoShape;
use ExeQue\Qr\Logos\Logo;
use ExeQue\Qr\Logos\LogoStyle;
use ExeQue\Qr\Registries\FilterRegistry;
use ExeQue\Qr\Registries\GradientRegistry;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\Renderer;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Rendering\StyleSerializer;
use ExeQue\Qr\Shapes\Enums\Module;
use ExeQue\Qr\Support\Hydrate;
use ExeQue\Qr\Support\Normalizer;

covers(
    \ExeQue\Qr\Exceptions\InvalidFilterException::class,
    StyleSerializer::class,
    Style::class,
    Hydrate::class,
    Normalizer::class,
    GradientRegistry::class,
    FilterRegistry::class,
    Region::class,
    InvalidStyleException::class,
    RegistryException::class,
    LinearGradient::class,
    RadialGradient::class,
    ConicGradient::class,
    DiamondGradient::class,
    MeshGradient::class,
    ByRole::class,
    InRegion::class,
    GradientFill::class,
    EyePaint::class,
    MixedShape::class,
    Compound::class
);

/**
 * The same style survives being written out and read back, as JSON in between.
 */
function roundTrip(Style $style): Style
{
    return Style::fromArray(json_decode((string) json_encode($style), true));
}

function renders(Style $style): string
{
    return new Renderer()->render(new Encoder()->encode('https://example.com/e/ABCD1234', ErrorCorrection::High), $style)->toSnapshot();
}

it('leaves settings at their defaults out', function (): void {
    expect(Style::default()->toArray())->toBe([])
        ->and(Style::default()->withMargin(2)->toArray())->toBe(['margin' => 2])
        ->and(Style::default()->withBackground(null)->toArray())->toBe(['transparent' => true]);
});

it('reads back every built-in filter', function (NamedFilter $filter): void {
    $style = Style::default()->withFilter($filter);

    expect(roundTrip($style)->toArray())->toBe($style->toArray())
        ->and(renders(roundTrip($style)))->toBe(renders($style));
})->with([
    'mixed-rotation' => [fn () => new MixedRotation(steps: [0, 90])->only(Role::Data)],
    'mixed-size' => [fn () => new MixedSize(min: 0.9, seed: 4)],
    'mixed-offset' => [fn () => new MixedOffset()->in(Region::rect(0, 0, 10, 10))],
    'mixed-skew' => [fn () => new MixedSkew(x: 4)->in(Region::cells([[10, 11], [12, 13]]))],
    'mixed-opacity' => [fn () => new MixedOpacity(min: 0.8)->in(Region::logo())],
    'mixed-shape' => [fn () => new MixedShape([Module::Square, 'square'], weights: [1, 2])],
    'palette' => [fn () => new Palette(['#be123c', 'oklch(45% 0.2 293)'])],
    'jitter' => [fn () => new Jitter(rotate: 5)],
    'gradient-fill' => [fn () => new GradientFill(LinearGradient::make('#be123c', '#1e1b4b')->angle(45))],
    'by-role' => [fn () => new ByRole([Role::Timing, 'alignment'], new MixedSize(min: 0.8))],
    'region' => [fn () => new InRegion(Region::circle(14, 14, 5), new MixedOpacity(min: 0.6))],
    'wave' => [fn () => new Wave(amplitude: 10)],
    'radial' => [fn () => new Radial(outer: 0.9)],
    'eye-tilt' => [fn () => new EyeTilt(outer: [-4, 4])->corners(Corner::TopRight)],
    'eye-size' => [fn () => new EyeSize(inner: 0.9)],
    'eye-paint' => [fn () => new EyePaint(outer: '#be123c', inner: RadialGradient::make('#be123c', '#1e1b4b'))],
    'compound' => [fn () => new Compound(new MixedOpacity(min: 0.8), new EyeTilt())],
]);

it('reads back every kind of gradient with all its settings', function (Gradient $gradient): void {
    $style = Style::default()->withPaint($gradient)->withBackground(MeshGradient::make([['#fff', '#fff1f2'], ['#eef2ff', '#fff']]));

    expect(roundTrip($style)->toArray())->toBe($style->toArray())
        ->and($style->toArray()['modules']['paint']['kind'])->toBeIn(['linear', 'radial', 'conic', 'diamond']);
})->with([
    'linear' => [fn () => LinearGradient::make()->stop('#be123c', 0, hint: 0.3)->stop('#1e1b4b', 1)->between(0, 0, 1, 0.5)->spread(Spread::Reflect)->in(ColorSpace::Oklch, HueInterpolation::Longer)],
    'radial' => [fn () => RadialGradient::make('#be123c', '#1e1b4b')->center(0.4, 0.5)->radius(0.6, 0.4)->focal(0.45, 0.5)->box(GradientBox::Canvas)],
    'conic' => [fn () => ConicGradient::make('#be123c', '#1e1b4b')->startAngle(30)->center(0.5, 0.4)->sampling(Sampling::PerModule)],
    'diamond' => [fn () => DiamondGradient::make('#be123c', '#1e1b4b')->radius(0.7)->center(0.3, 0.3)],
]);

it('reads back a whole design', function (): void {
    $eye = Style::default()->eye(Corner::TopLeft);
    $style = Style::default()
        ->mirrored()
        ->withMargin(3)
        ->withPaint(new Hex('#1c1917'))
        ->withBackground(new Hex('#fff7ed'))
        ->withModules(Style::default()->modules->withPaint(new Hex('#1c1917'))->withOpacity(0.9)->withRotate(5))
        ->withEye(Corner::TopRight, $eye->withOuter($eye->outer->withPaint(new Hex('#be123c'))->withSize(0.95))->withInner($eye->inner->withOpacity(0.8)))
        ->withFilters(new MixedOpacity(min: 0.85), new EyeTilt());

    expect(roundTrip($style)->toArray())->toBe($style->toArray())
        ->and(roundTrip($style)->equals($style))->toBeTrue()
        ->and(renders(roundTrip($style)))->toBe(renders($style))
        ->and($style->toArray())->toMatchSnapshot();
});

it('reads back a transparent background', function (): void {
    expect(roundTrip(Style::default()->withBackground(null))->background)->toBeNull();
});

it('reads back a logo from its source', function (): void {
    $logo = Logo::fromFile(dirname(__DIR__, 2).'/Fixtures/logos/badge.svg');
    $style = Style::default()->withLogo(new LogoStyle($logo, 0.25, 2, LogoShape::Circle, new Hex('#ffffff'), 1, null));

    $stored = $style->toArray();
    $stored['logo']['source'] = $logo->toDataUri();

    expect(Style::fromArray($stored)->toArray())->toBe($style->toArray())
        ->and(Style::fromArray(['logo' => ['source' => dirname(__DIR__, 2).'/Fixtures/logos/badge.svg']])->logo?->logo->hash)->toBe($logo->hash)
        ->and(Style::fromArray(['logo' => ['source' => $logo]])->logo?->logo)->toBe($logo);
});

it('refuses a logo it cannot read back', function (): void {
    Style::fromArray(['logo' => ['hash' => 'abc', 'size' => 0.2]]);
})->throws(InvalidStyleException::class, 'A stored logo has no source to read it from');

it('refuses what it cannot read', function (array $data, string $exception, string $message): void {
    expect(fn () => Style::fromArray($data))->toThrow($exception, $message);
})->with([
    'paint' => [['background' => 12], InvalidStyleException::class, 'A stored paint cannot be read: 12.'],
    'shape' => [['modules' => ['shape' => 'NoSuchShape']], InvalidStyleException::class, 'A stored shape cannot be read: "NoSuchShape".'],
    'shape of no kind' => [['modules' => ['shape' => 5]], InvalidStyleException::class, 'A stored shape cannot be read: 5.'],
    'gradient kind' => [['background' => ['kind' => 'swirl']], RegistryException::class, 'The gradient "swirl" cannot be stored or read back'],
    'region' => [['filters' => [['name' => 'mixed-size', 'options' => ['in' => ['kind' => 'blob']]]]], \ExeQue\Qr\Exceptions\InvalidFilterException::class, '"blob" is not a kind of region'],
    'filter scope' => [['filters' => [['name' => 'by-role', 'options' => ['roles' => ['data'], 'filter' => ['name' => 'mixed-size'], 'only' => ['data']]]]], RegistryException::class, 'The by-role filter cannot be limited to roles, a region or corners.'],
]);

it('stores a paint as a color or a gradient, and nothing else', function (): void {
    expect(fn () => app(GradientRegistry::class)->dehydrate(new class () implements Gradient {
        public function colorAt(float $x, float $y): \ExeQue\Qr\Contracts\Color
        {
            return new Hex('#000');
        }

        public function stops(): array
        {
            return [];
        }
    }))->toThrow(RegistryException::class, 'cannot be stored or read back');
});

it('refuses a filter that is not one for modules or eyes', function (): void {
    app(FilterRegistry::class)->register('odd', new class () implements NamedFilter {
        public function name(): string
        {
            return 'odd';
        }

        public function options(): array
        {
            return [];
        }
    });

    Hydrate::filter(['name' => 'odd']);
})->throws(InvalidStyleException::class, 'A stored filter cannot be read: "odd".');

it('refuses a scoped filter that is not a module filter', function (): void {
    new ByRole(['data'], ['name' => 'eye-tilt']);
})->throws(\ExeQue\Qr\Exceptions\InvalidFilterException::class, 'A filter limited to roles or a region is a named module filter.');

it('reads shapes by their registered names, as config holds them', function (): void {
    $style = Style::fromArray([
        'modules' => ['shape' => ['class' => 'drops', 'options' => ['point' => 'random']]],
        'eyes' => ['top-left' => ['outer' => ['shape' => 'leaf'], 'inner' => ['shape' => 'pill']]],
    ]);

    expect($style->modules->shape)->toBeInstanceOf(\ExeQue\Qr\Shapes\Modules\Drop::class)
        ->and($style->modules->shape->point)->toBe(\ExeQue\Qr\Shapes\Enums\Direction::Random)
        ->and($style->eye(\ExeQue\Qr\Rendering\Enums\Corner::TopLeft)->outer->shape)->toBeInstanceOf(\ExeQue\Qr\Shapes\OuterEyes\Rounded::class)
        ->and(fn () => Style::fromArray(['modules' => ['shape' => 'no-such-shape']]))->toThrow(\ExeQue\Qr\Exceptions\InvalidStyleException::class)
        ->and(fn () => \ExeQue\Qr\Support\Hydrate::shape('dots-medium', \ExeQue\Qr\Filters\Contracts\NamedFilter::class))->toThrow(\ExeQue\Qr\Exceptions\InvalidStyleException::class);
});
