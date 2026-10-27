<?php

declare(strict_types=1);

namespace Tests\Feature\Registries;

use ExeQue\Qr\Exceptions\InvalidOptionException;
use ExeQue\Qr\Exceptions\RegistryException;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Factory;
use ExeQue\Qr\Filters\Enums\Filter;
use ExeQue\Qr\Filters\Jitter;
use ExeQue\Qr\Filters\MixedOpacity;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\RoundedRect;
use ExeQue\Qr\Gradients\MeshGradient;
use ExeQue\Qr\Qr as QrManager;
use ExeQue\Qr\QrServiceProvider;
use ExeQue\Qr\Registries\Concerns\IsRegistry;
use ExeQue\Qr\Registries\ModuleRegistry;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrModule;
use ExeQue\Qr\Shapes\Enums\Eye;
use ExeQue\Qr\Shapes\Enums\Module;
use ExeQue\Qr\Shapes\Modules\Square as SquareModule;
use ExeQue\Qr\Shapes\OuterEyes\Square as SquareOuter;

covers(IsRegistry::class, ModuleRegistry::class, \ExeQue\Qr\Registries\OuterEyeRegistry::class, \ExeQue\Qr\Registries\InnerEyeRegistry::class, \ExeQue\Qr\Registries\EyeRegistry::class, \ExeQue\Qr\Registries\FilterRegistry::class, \ExeQue\Qr\Registries\GradientRegistry::class, RegistryException::class, Factory::class, InvalidOptionException::class, QrManager::class, QrServiceProvider::class, Module::class, Filter::class, MeshGradient::class);

pest()->group('registries');

final class DotModule implements ModuleShape
{
    public function __construct(
        public float $diameter = 0.8,
    ) {
    }

    public function draw(ModuleContext $cell): Path
    {
        return RoundedRect::circle(0, 0, $this->diameter / 2);
    }
}

enum OwnModules: string implements ProvidesQrModule
{
    case Dots = 'own-dots';
    case Squares = 'own-squares';

    public function qrModule(): ModuleShape
    {
        return $this === self::Dots ? new DotModule() : new SquareModule();
    }
}

describe('registering', function (): void {
    it('holds the built-in parts under their names', function (): void {
        expect(Qr::modules()->names())->toContain('square')
            ->and(Qr::outerEyes()->get('square'))->toBeInstanceOf(SquareOuter::class)
            ->and(Qr::eyes()->get('square'))->toBe(Eye::Square)
            ->and(Qr::filters()->names())->toContain('mixed-opacity', 'compound')
            ->and(Qr::gradients()->names())->toBe(['linear', 'radial', 'conic', 'diamond', 'mesh']);
    });

    // Each row is one closure, giving the value and the diameter it should come out with:
    // Pest only calls a dataset closure when it is the row's only value.
    it('takes every form', function (array $case): void {
        [$value, $diameter] = $case;

        Qr::modules()->register('dots', $value);

        $shape = Qr::modules()->get('dots');

        expect($shape)->toBeInstanceOf(DotModule::class)
            ->and($shape->diameter)->toBe($diameter);
    })->with([
        'class name' => [fn () => [DotModule::class, 0.8]],
        'class with fixed options' => [fn () => [[DotModule::class, 'diameter' => 0.6], 0.6]],
        'closure' => [fn () => [fn (): DotModule => new DotModule(0.5), 0.5]],
        'instance' => [fn () => [new DotModule(0.4), 0.4]],
        'provider' => [fn () => [OwnModules::Dots, 0.8]],
    ]);

    it('registers every case of an enum under its value', function (): void {
        Qr::modules()->register(OwnModules::class);

        expect(Qr::modules()->get('own-dots'))->toBeInstanceOf(DotModule::class)
            ->and(Qr::modules()->get('own-squares'))->toBeInstanceOf(SquareModule::class);
    });

    it('lets code win over config, and config over the built-ins', function (): void {
        config()->set('qr.modules', ['square' => DotModule::class, 'dots' => [DotModule::class, 'diameter' => 0.6], OwnModules::class]);
        $this->refreshApplication();
        config()->set('qr.modules', ['square' => DotModule::class, 'dots' => [DotModule::class, 'diameter' => 0.6], OwnModules::class]);

        expect(Qr::modules()->get('square'))->toBeInstanceOf(DotModule::class)
            ->and(Qr::modules()->get('own-dots'))->toBeInstanceOf(DotModule::class);

        Qr::modules()->register('dots', [DotModule::class, 'diameter' => 0.2]);

        expect(Qr::modules()->get('dots')->diameter)->toBe(0.2);
    });

    it('reads the config only when first used', function (): void {
        $registry = Qr::modules();
        config()->set('qr.modules', ['late' => DotModule::class]);

        expect($registry->has('late'))->toBeTrue();
    });

    it('forgets a name, a built-in one too, until registered again', function (): void {
        Qr::modules()->forget('square');

        expect(Qr::modules()->has('square'))->toBeFalse();

        Qr::modules()->register('square', SquareModule::class);

        expect(Qr::modules()->has('square'))->toBeTrue();
    });

    it('keeps each kind of part apart', function (): void {
        Qr::modules()->register('bloom', DotModule::class);

        expect(Qr::eyes()->has('bloom'))->toBeFalse()
            ->and(fn () => Qr::modules()->register('ring', SquareOuter::class) ?? Qr::modules()->get('ring'))
            ->toThrow(RegistryException::class, 'The module "ring" is neither a '.ModuleShape::class.' nor provides one');
    });

    it('starts every application with fresh registries', function (): void {
        Qr::modules()->register('leftover', DotModule::class);

        $this->refreshApplication();

        expect(Qr::modules()->has('leftover'))->toBeFalse();
    });

    it('names its kind and reads its own config key', function (string $accessor, string $key, string $kind, mixed $value): void {
        config()->set("qr.{$key}", ['own' => $value]);

        expect(Qr::$accessor()->has('own'))->toBeTrue()
            ->and(fn () => Qr::$accessor()->get('nope'))->toThrow(RegistryException::class, "No {$kind} is registered as \"nope\"");
    })->with([
        ['modules', 'modules', 'module', SquareModule::class],
        ['outerEyes', 'outer_eyes', 'outer eye', SquareOuter::class],
        ['innerEyes', 'inner_eyes', 'inner eye', \ExeQue\Qr\Shapes\InnerEyes\Square::class],
        ['eyes', 'eyes', 'eye', Eye::Square],
        ['filters', 'filters', 'filter', Jitter::class],
        ['gradients', 'gradients', 'gradient', MeshGradient::class],
    ]);
});

describe('building', function (): void {
    it('builds with options, over the fixed ones', function (): void {
        Qr::modules()->register('dots', [DotModule::class, 'diameter' => 0.6]);
        Qr::filters()->register('soft', [MixedOpacity::class, 'min' => 0.5]);

        expect(Qr::modules()->get('dots', ['diameter' => 0.9])->diameter)->toBe(0.9)
            ->and(Qr::filters()->get('soft')->options()['min'])->toBe(0.5)
            ->and(Qr::filters()->get('mixed-opacity', ['min' => 0.6])->options()['min'])->toBe(0.6)
            ->and(Qr::gradients()->get('mesh', ['rows' => [['red', 'blue'], ['green', 'white']]]))->toBeInstanceOf(MeshGradient::class);
    });

    it('passes options to a closure', function (): void {
        Qr::modules()->register('dots', fn (float $diameter = 0.8): DotModule => new DotModule($diameter));

        expect(Qr::modules()->get('dots', ['diameter' => 0.3])->diameter)->toBe(0.3);
    });

    it('refuses options a part does not take', function (): void {
        Qr::filters()->get('jitter', ['spin' => 4]);
    })->throws(InvalidOptionException::class, Jitter::class.' has no option "spin"; it takes rotate, size, offset, seed.');

    it('lists every entry built', function (): void {
        expect(array_keys(Qr::outerEyes()->all()))->toBe(array_column(\ExeQue\Qr\Shapes\Enums\OuterEye::cases(), 'value'));
    });

    it('builds the built-in filters by their enum', function (): void {
        expect(Filter::Jitter->qrFilter())->toBeInstanceOf(Jitter::class)
            ->and(Filter::Jitter->class())->toBe(Jitter::class)
            ->and(Module::Square->qrModule())->toBeInstanceOf(SquareModule::class);
    });

    it('refuses to make an eye or a gradient from something that only provides another part', function (string $accessor): void {
        Qr::$accessor()->register('wrong', Module::Square);
        Qr::$accessor()->get('wrong');
    })->with(['eyes', 'gradients'])->throws(RegistryException::class, 'is neither');

    it('asks a provider for its part', function (string $accessor, object $provider, string $class): void {
        Qr::$accessor()->register('provided', $provider);

        expect(Qr::$accessor()->get('provided'))->toBeInstanceOf($class);
    })->with([
        ['outerEyes', \ExeQue\Qr\Shapes\Enums\OuterEye::Square, SquareOuter::class],
        ['innerEyes', \ExeQue\Qr\Shapes\Enums\InnerEye::Square, \ExeQue\Qr\Shapes\InnerEyes\Square::class],
        ['filters', Filter::Jitter, Jitter::class],
    ]);

});

describe('labels', function (): void {
    it('labels in the application\'s language, and an entry without a label by its name in words', function (): void {
        Qr::modules()->register('my-pill', SquareModule::class);
        app()->setLocale('da');

        expect(Qr::modules()->label('dots-medium'))->not->toBe('Medium dots')
            ->and(Qr::modules()->label('my-pill'))->toBe('My Pill')
            ->and(Qr::modules()->labels())->toHaveKey('my-pill', 'My Pill')->toHaveCount(count(Qr::modules()->names()));

        app()->setLocale('en');
        app('translator')->addLines(['labels.modules.my-pill' => 'Pill, mine'], 'en', 'qr');

        expect(Qr::modules()->label('dots-medium'))->toBe('Medium dots')
            ->and(Qr::modules()->label('my-pill'))->toBe('Pill, mine');
    });
});

describe('errors', function (): void {
    it('says which names it has when one is unknown', function (): void {
        Qr::outerEyes()->forget('soft');
        Qr::outerEyes()->get('triangle');
    })->throws(RegistryException::class, 'No outer eye is registered as "triangle"; registered are: square, soft-square, rounded, circle, round-corner, soft-round-corner, squircle, octagon, hexagon, hexagon-standing, leaf, drop, scalloped, dotted-ring.');

    it('says when it has none', function (): void {
        foreach (Qr::outerEyes()->names() as $name) {
            Qr::outerEyes()->forget($name);
        }

        Qr::outerEyes()->get('octagon');
    })->throws(RegistryException::class, 'No outer eye is registered as "octagon"; none are registered.');

    it('refuses values that are not objects at all', function (): void {
        Qr::modules()->register('number', 42);
        Qr::modules()->get('number');
    })->throws(RegistryException::class, 'The module "number" is neither');
});
