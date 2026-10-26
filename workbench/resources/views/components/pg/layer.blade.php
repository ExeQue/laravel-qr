{{-- One paint: a color or any kind of gradient, with its placement and blending. --}}
@props(['spec', 'path', 'kinds' => null])
<div class="paint-layer">
    <label>Kind
        <select wire:model.live="{{ $path }}.type">
            @foreach ($kinds ?? ['solid' => 'Solid', 'linear' => 'Linear gradient', 'radial' => 'Radial gradient', 'conic' => 'Conic gradient', 'diamond' => 'Diamond gradient', 'mesh' => 'Mesh gradient'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </label>

    @if ($spec['type'] === 'solid')
        <x-pg.color :path="$path.'.color'" label="Color" />
    @elseif ($spec['type'] === 'mesh')
        <div class="row">
            @foreach ($spec['mesh'] as $index => $color)
                <x-pg.color :path="$path.'.mesh.'.$index" :label="['Top left', 'Top right', 'Bottom left', 'Bottom right'][$index]" />
            @endforeach
        </div>
    @else
        @foreach ($spec['stops'] as $index => $stop)
            <div class="row">
                <x-pg.color :path="$path.'.stops.'.$index.'.color'" :label="'Stop '.($index + 1)" />
                <x-pg.range :path="$path.'.stops.'.$index.'.position'" label="At" :value="$stop['position']" handle />
            </div>
        @endforeach

        @if ($spec['type'] === 'linear')
            <x-pg.range :path="$path.'.angle'" label="Angle" :value="$spec['angle']" max="360" step="1" unit="°" />
        @else
            <div class="row">
                <x-pg.range :path="$path.'.centerX'" label="Center x" :value="$spec['centerX']" min="-0.5" max="1.5" />
                <x-pg.range :path="$path.'.centerY'" label="Center y" :value="$spec['centerY']" min="-0.5" max="1.5" />
            </div>
            @if ($spec['type'] === 'conic')
                <x-pg.range :path="$path.'.start'" label="Start angle" :value="$spec['start']" max="360" step="1" unit="°" />
            @else
                <x-pg.range :path="$path.'.radius'" label="Radius" :value="$spec['radius']" min="0.05" max="1.5" />
            @endif
            @if ($spec['type'] === 'radial')
                <label class="inline"><input type="checkbox" wire:model.live="{{ $path }}.focal"> Focal point</label>
                @if ($spec['focal'])
                    <div class="row">
                        <x-pg.range :path="$path.'.focalX'" label="Focal x" :value="$spec['focalX']" min="-0.5" max="1.5" />
                        <x-pg.range :path="$path.'.focalY'" label="Focal y" :value="$spec['focalY']" min="-0.5" max="1.5" />
                    </div>
                @endif
            @endif
        @endif

        <div class="row">
            <label>Blend in
                <select wire:model.live="{{ $path }}.space">
                    @foreach (\ExeQue\Qr\Gradients\Enums\ColorSpace::cases() as $case)<option value="{{ $case->value }}">{{ $case->value }}</option>@endforeach
                </select>
            </label>
            <label>Hue
                <select wire:model.live="{{ $path }}.hue">
                    @foreach (\ExeQue\Qr\Gradients\Enums\HueInterpolation::cases() as $case)<option value="{{ $case->value }}">{{ $case->value }}</option>@endforeach
                </select>
            </label>
        </div>
        <div class="row">
            <label>Beyond the ends
                <select wire:model.live="{{ $path }}.spread">
                    @foreach (\ExeQue\Qr\Gradients\Enums\Spread::cases() as $case)<option value="{{ $case->value }}">{{ $case->value }}</option>@endforeach
                </select>
            </label>
            <label>Spans
                <select wire:model.live="{{ $path }}.box">
                    @foreach (\ExeQue\Qr\Gradients\Enums\GradientBox::cases() as $case)<option value="{{ $case->value }}">{{ $case->value }}</option>@endforeach
                </select>
            </label>
        </div>
    @endif

    @if ($spec['type'] !== 'solid')
        <label>Sampling
            <select wire:model.live="{{ $path }}.sampling">
                @foreach (\ExeQue\Qr\Gradients\Enums\Sampling::cases() as $case)<option value="{{ $case->value }}">{{ $case->value }}</option>@endforeach
            </select>
        </label>
    @endif
</div>
