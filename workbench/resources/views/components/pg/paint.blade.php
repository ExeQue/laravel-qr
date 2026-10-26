{{-- A paint: layers from the bottom up, each a color or a gradient faded in by its opacity, each folding away. --}}
@props(['spec', 'path'])
@php($kinds = ['solid' => 'solid', 'linear' => 'linear', 'radial' => 'radial', 'conic' => 'conic', 'diamond' => 'diamond', 'mesh' => 'mesh'])
<div class="paint">
    @foreach ($spec['layers'] as $index => $layer)
        @if (count($spec['layers']) === 1)
            <x-pg.layer :spec="$layer" :path="$path.'.layers.'.$index" />
        @else
            <details class="layer" open wire:ignore.self wire:key="{{ $path }}-layer-{{ $index }}">
                <summary>
                    <span>Layer {{ $index + 1 }} <span class="hint">{{ $kinds[$layer['type']] ?? $layer['type'] }}, {{ round($layer['opacity'] * 100) }} %</span></span>
                    <button type="button" wire:click="removeLayer('{{ $path }}', {{ $index }})" aria-label="Remove layer {{ $index + 1 }}">✕</button>
                </summary>
                <x-pg.range :path="$path.'.layers.'.$index.'.opacity'" label="Opacity" :value="$layer['opacity']" />
                <x-pg.layer :spec="$layer" :path="$path.'.layers.'.$index" />
            </details>
        @endif
    @endforeach
    <button type="button" wire:click="addLayer('{{ $path }}')">Add layer</button>
</div>
