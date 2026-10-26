<?php

declare(strict_types=1);

namespace Workbench\App\Livewire\Concerns;

use ExeQue\Qr\Colors\Color;
use ExeQue\Qr\Contracts\Paint;
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
use ExeQue\Qr\Paints\Layer;
use ExeQue\Qr\Paints\Layered;

/**
 * Paints as the playground's inputs hold them: a stack of layers, each a color or a
 * gradient with its settings, made into paints and into the PHP that makes them.
 */
trait BuildsPaints
{
    public function addLayer(string $path): void
    {
        $layers = data_get($this, "{$path}.layers");
        $layers[] = [...self::layerSpec('#7c3aed', 'radial'), 'opacity' => 0.5, 'box' => $layers[0]['box']];

        data_set($this, "{$path}.layers", $layers);
    }

    public function removeLayer(string $path, int $index): void
    {
        $layers = data_get($this, "{$path}.layers");
        unset($layers[$index]);

        data_set($this, "{$path}.layers", array_values($layers));
    }

    /**
     * @return array{layers: list<array<string, mixed>>}
     */
    private static function paintSpec(string $color, string $type = 'solid'): array
    {
        return ['layers' => [self::layerSpec($color, $type)]];
    }

    /**
     * @return array<string, mixed>
     */
    private static function layerSpec(string $color, string $type = 'solid'): array
    {
        return [
            'type' => $type,
            'opacity' => 1.0,
            'color' => $color,
            'stops' => [['color' => $color, 'position' => 0.0], ['color' => '#1e1b4b', 'position' => 1.0]],
            'angle' => 135.0,
            'centerX' => 0.5,
            'centerY' => 0.5,
            'radius' => 0.5,
            'focal' => false,
            'focalX' => 0.4,
            'focalY' => 0.4,
            'start' => 0.0,
            'mesh' => ['#be123c', '#7c3aed', '#0f766e', '#1e1b4b'],
            'space' => ColorSpace::Oklab->value,
            'hue' => HueInterpolation::Shorter->value,
            'spread' => Spread::Pad->value,
            'box' => GradientBox::Code->value,
            'sampling' => Sampling::Continuous->value,
        ];
    }

    /**
     * @param array{layers: list<array<string, mixed>>} $spec
     */
    private function paintOf(array $spec): Paint|string
    {
        $layers = $spec['layers'];

        if (count($layers) === 1 && (float) $layers[0]['opacity'] >= 1) {
            return $this->layerOf($layers[0]);
        }

        return new Layered(array_map(fn (array $layer): Layer => new Layer(
            is_string($paint = $this->layerOf($layer)) ? Color::make($paint) : $paint,
            (float) $layer['opacity'],
        ), $layers));
    }

    /**
     * @param array<string, mixed> $spec
     */
    private function layerOf(array $spec): Paint|string
    {
        if ($spec['type'] === 'solid') {
            return (string) $spec['color'];
        }

        if ($spec['type'] === 'mesh') {
            [$a, $b, $c, $d] = $spec['mesh'];

            return MeshGradient::make([[$a, $b], [$c, $d]]);
        }

        $gradient = match ($spec['type']) {
            'radial' => RadialGradient::make(),
            'conic' => ConicGradient::make(),
            'diamond' => DiamondGradient::make(),
            default => LinearGradient::make(),
        };

        foreach ($spec['stops'] as $stop) {
            $gradient = $gradient->stop((string) $stop['color'], (float) $stop['position']);
        }

        [$x, $y] = [(float) $spec['centerX'], (float) $spec['centerY']];

        $gradient = match (true) {
            $gradient instanceof LinearGradient => $gradient->angle((float) $spec['angle']),
            $gradient instanceof RadialGradient => (bool) $spec['focal']
                ? $gradient->center($x, $y)->radius((float) $spec['radius'])->focal((float) $spec['focalX'], (float) $spec['focalY'])
                : $gradient->center($x, $y)->radius((float) $spec['radius']),
            $gradient instanceof ConicGradient => $gradient->center($x, $y)->startAngle((float) $spec['start']),
            $gradient instanceof DiamondGradient => $gradient->center($x, $y)->radius((float) $spec['radius']),
        };

        /** @var Gradient $gradient */
        return $gradient
            ->in(ColorSpace::from((string) $spec['space']), HueInterpolation::from((string) $spec['hue']))
            ->spread(Spread::from((string) $spec['spread']))
            ->box(GradientBox::from((string) $spec['box']))
            ->sampling(Sampling::from((string) $spec['sampling']));
    }

    /**
     * The PHP that makes the paint.
     *
     * @param array{layers: list<array<string, mixed>>} $spec
     * @param array<string, true> $imports Filled with the classes the PHP uses.
     */
    private function paintPhp(array $spec, array &$imports, string $indent): string
    {
        $layers = $spec['layers'];

        if (count($layers) === 1 && (float) $layers[0]['opacity'] >= 1) {
            return $this->layerPhp($layers[0], $imports, $indent);
        }

        $imports[Layered::class] = true;
        $php = 'Layered::make('.$this->layerPhp($layers[0], $imports, $indent.'    ').')';

        foreach (array_slice($layers, 1) as $layer) {
            $php .= "\n{$indent}    ->layer(".$this->layerPhp($layer, $imports, $indent.'    ').', '.self::literal((float) $layer['opacity']).')';
        }

        return $php;
    }

    /**
     * @param array<string, mixed> $spec
     * @param array<string, true> $imports
     */
    private function layerPhp(array $spec, array &$imports, string $indent): string
    {
        if ($spec['type'] === 'solid') {
            return self::literal((string) $spec['color']);
        }

        if ($spec['type'] === 'mesh') {
            $imports[MeshGradient::class] = true;
            [$a, $b, $c, $d] = array_map(fn (mixed $color): string => self::literal((string) $color), $spec['mesh']);

            return "MeshGradient::make([[{$a}, {$b}], [{$c}, {$d}]])";
        }

        $class = match ($spec['type']) {
            'radial' => RadialGradient::class,
            'conic' => ConicGradient::class,
            'diamond' => DiamondGradient::class,
            default => LinearGradient::class,
        };
        $imports[$class] = true;
        $defaults = self::layerSpec('#000000', (string) $spec['type']);
        $calls = array_map(fn (array $stop): string => '->stop('.self::literal((string) $stop['color']).', '.self::literal((float) $stop['position']).')', $spec['stops']);
        $center = '->center('.self::literal((float) $spec['centerX']).', '.self::literal((float) $spec['centerY']).')';

        $calls = [...$calls, ...match ($spec['type']) {
            'linear' => ['->angle('.self::literal((float) $spec['angle']).')'],
            'conic' => [$center, '->startAngle('.self::literal((float) $spec['start']).')'],
            'radial' => [$center, '->radius('.self::literal((float) $spec['radius']).')', ...((bool) $spec['focal'] ? ['->focal('.self::literal((float) $spec['focalX']).', '.self::literal((float) $spec['focalY']).')'] : [])],
            default => [$center, '->radius('.self::literal((float) $spec['radius']).')'],
        }];

        foreach (['space' => ColorSpace::class, 'spread' => Spread::class, 'box' => GradientBox::class, 'sampling' => Sampling::class] as $key => $enum) {
            if ($spec[$key] !== $defaults[$key] || ($key === 'space' && $spec['hue'] !== $defaults['hue'])) {
                $imports[$enum] = true;
                $case = self::case($enum::from((string) $spec[$key]));
                $calls[] = match ($key) {
                    'space' => "->in({$case}".($spec['hue'] === $defaults['hue'] ? '' : ', '.self::case(HueInterpolation::from((string) $spec['hue']))).')',
                    default => "->{$key}({$case})",
                };

                if ($key === 'space' && $spec['hue'] !== $defaults['hue']) {
                    $imports[HueInterpolation::class] = true;
                }
            }
        }

        return class_basename($class).'::make()'.implode('', array_map(fn (string $call): string => "\n{$indent}    {$call}", $calls));
    }
}
