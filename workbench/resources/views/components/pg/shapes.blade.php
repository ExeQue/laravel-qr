@props(['kind', 'names', 'model', 'blank' => null])
{{-- Shapes to pick from by their icons; the name shows on hover and under the grid. --}}
<div class="shapes" role="radiogroup">
    @if ($blank !== null)
        <label class="shape" title="{{ $blank }}">
            <input type="radio" value="" wire:model.live="{{ $model }}">
            <span class="shape-blank">{{ $blank }}</span>
        </label>
    @endif
    @foreach ($names as $name)
        <label class="shape" title="{{ $name }}" wire:key="{{ $model }}-{{ $name }}">
            <input type="radio" value="{{ $name }}" wire:model.live="{{ $model }}">
            @switch ($kind)
                @case ('module')
                    <x-qr::module :shape="$name" />
                    @break
                @case ('outer')
                    <x-qr::outer-eye :shape="$name" />
                    @break
                @case ('inner')
                    <x-qr::inner-eye :shape="$name" />
                    @break
                @default
                    <x-qr::eye :eye="$name" />
            @endswitch
        </label>
    @endforeach
</div>
