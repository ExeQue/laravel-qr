{{-- A number on a slider, its value shown as it moves and sent once the slider rests; `handle` draws the handle alone. --}}
@props(['path', 'label', 'value', 'min' => 0, 'max' => 1, 'step' => 0.01, 'unit' => '', 'handle' => false])
<label class="range" x-data="{ shown: @js((float) $value) }">
    <span>{{ $label }} <output x-text="shown + @js($unit)"></output></span>
    <input
        type="range"
        @class(['handle-only' => $handle])
        min="{{ $min }}"
        max="{{ $max }}"
        step="{{ $step }}"
        x-on:input="shown = Number($event.target.value)"
        wire:model.live.debounce.150ms="{{ $path }}"
    >
</label>
