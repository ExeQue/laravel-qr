{{-- One rendered QR code: what it is, what it was drawn with and how long each phase took. Never its content. --}}
@php
    use ExeQue\Monocle\Dashboard\Format;
    use Illuminate\Support\Arr;

    $ms = fn (mixed $value): ?string => is_numeric($value) ? Format::ms((float) $value) : null;
    $tables = array_filter([
        'Style' => Arr::dot($row['style'] ?? []),
        'Options' => Arr::dot($row['options'] ?? []),
    ]);
@endphp
<div class="grid gap-4">
    <x-monocle::facts
        :items="[
            ['Kind', $row['kind'] ?? null],
            ['Host', $row['host'] ?? null],
            ['Format', $row['format'] ?? null],
            ['Cached', ($row['cached'] ?? false) ? 'yes' : null, 'text-ok'],
            ['Version', $row['version'] ?? null],
            ['Error correction', $row['error_correction'] ?? null],
            ['Modules', $row['modules'] ?? null],
            ['Min version', $row['min_version'] ?? null],
            ['Mask', $row['mask'] ?? null],
            ['Length', $row['length'] ?? null],
            ['Cacheable', ($row['cacheable'] ?? true) ? null : 'no', 'text-warn'],
        ]"
    />

    <x-monocle::facts
        :items="[
            ['Module shape', $row['module_shape'] ?? null],
            ['Outer eye', $row['outer_eye'] ?? null],
            ['Inner eye', $row['inner_eye'] ?? null],
            ['Filters', implode(', ', $row['filters'] ?? [])],
            ['Logo', $row['logo'] ?? null],
        ]"
    />

    <x-monocle::facts
        :items="[
            ['Duration', $ms($row['duration'] ?? null)],
            ['Encode', $ms($row['encode_ms'] ?? null)],
            ['Render', $ms($row['render_ms'] ?? null)],
            ['Write', $ms($row['write_ms'] ?? null)],
        ]"
    />

    @if (! empty($row['guards']))
        <x-monocle::block title="Scan guards">
            <ul class="m-0 grid gap-0.5 pl-4 text-xs">
                @foreach ($row['guards'] as $guard)
                    <li class="text-warn">{{ $guard }}</li>
                @endforeach
            </ul>
        </x-monocle::block>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($tables as $title => $values)
            <x-monocle::block :title="$title">
                <table class="w-full border-collapse font-mono text-xs">
                    <tbody>
                        @foreach ($values as $key => $value)
                            @php($parent = str_contains((string) $key, '.') ? substr((string) $key, 0, strrpos((string) $key, '.') + 1) : '')
                            <tr class="border-line border-t">
                                <td class="w-0 py-0.5 pr-6 whitespace-nowrap"><span class="text-muted">{{ $parent }}</span>{{ substr((string) $key, strlen($parent)) }}</td>
                                <td class="py-0.5 [overflow-wrap:anywhere]">
                                    @if (is_string($value) && str_contains($value, '\\') && class_exists($value))
                                        <x-monocle::name :value="$value" />
                                    @else
                                        {{ is_bool($value) ? var_export($value, true) : ($value === null ? 'null' : (is_array($value) ? '[]' : $value)) }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-monocle::block>
        @endforeach
    </div>

    <x-monocle::origin :origin="$row['origin'] ?? null" />
</div>
