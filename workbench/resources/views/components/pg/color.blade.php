{{--
    A color with its alpha, kept in the Livewire property at `path` as #rrggbb, or #rrggbbaa when not
    fully opaque. `optional` adds a switch for no color at all (an empty string).
--}}
@props(['path', 'label' => null, 'optional' => false])
<div
    class="color-field"
    x-data="{
        value: $wire.entangle('{{ $path }}').live,
        get hex() {
            const value = String(this.value ?? '');
            return /^#[0-9a-f]{6}([0-9a-f]{2})?$/i.test(value) ? value.slice(0, 7) : '#000000';
        },
        get alpha() {
            const value = String(this.value ?? '');
            return /^#[0-9a-f]{8}$/i.test(value) ? parseInt(value.slice(7), 16) / 255 : 1;
        },
        set(hex, alpha) {
            const byte = Math.round(alpha * 255);
            this.value = hex + (byte >= 255 ? '' : byte.toString(16).padStart(2, '0'));
        },
    }"
>
    @if ($label)<span class="color-label">{{ $label }}</span>@endif
    @if ($optional)
        <input type="checkbox" :checked="!! value" @change="value = $event.target.checked ? '#000000' : ''" aria-label="Set {{ strtolower($label ?? 'color') }}">
    @endif
    <span class="color-inputs" @if ($optional) x-show="!! value" @endif>
        <input type="color" :value="hex" @change="set($event.target.value, alpha)" aria-label="{{ $label ?? 'Color' }}">
        <input type="range" min="0" max="1" step="0.01" :value="alpha" @change="set(hex, Number($event.target.value))" aria-label="{{ $label ?? 'Color' }} alpha">
        <output x-text="Math.round(alpha * 100) + '%'"></output>
    </span>
</div>
