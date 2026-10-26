{{-- A shape's own options, as the shape has them; `choices` lists the values of those that are enums. --}}
@props(['options', 'path', 'choices' => []])
@foreach ($options as $name => $value)
    @if (isset($choices[$name]))
        <label>{{ $name }}
            <select wire:model.live="{{ $path }}.{{ $name }}">
                @foreach ($choices[$name] as $choice)<option value="{{ $choice }}">{{ $choice }}</option>@endforeach
            </select>
        </label>
    @elseif (is_bool($value))
        <label class="inline"><input type="checkbox" wire:model.live="{{ $path }}.{{ $name }}"> {{ $name }}</label>
    @elseif (is_array($value))
        <div class="row four">
            @foreach ($value as $index => $part)
                <label>{{ $name }} {{ ['TR', 'BR', 'BL', 'TL'][$index] ?? $index }} <input type="number" step="0.05" wire:model.live.debounce.300ms="{{ $path }}.{{ $name }}.{{ $index }}"></label>
            @endforeach
        </div>
    @else
        <label>{{ $name }} <input type="number" step="{{ is_int($value) ? 1 : 0.01 }}" wire:model.live.debounce.300ms="{{ $path }}.{{ $name }}"></label>
    @endif
@endforeach
