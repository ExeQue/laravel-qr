<div class="playground">
    <div class="panel controls">
        <fieldset>
            <legend>Content</legend>
            <label>Text or URL <textarea rows="2" wire:model.live.debounce.300ms="content"></textarea></label>
            <div class="row">
                <label>Error correction
                    <select wire:model.live="errorCorrection">
                        <option value="L">Low (7 %)</option>
                        <option value="M">Medium (15 %)</option>
                        <option value="Q">Quartile (25 %)</option>
                        <option value="H">High (30 %)</option>
                    </select>
                </label>
                <label>At least version <input type="number" min="1" max="40" wire:model.live.debounce.300ms="minVersion"></label>
            </div>
            <label>Margin, modules <input type="number" min="0" max="10" wire:model.live="margin"></label>
        </fieldset>

        <details open wire:ignore.self>
            <summary>Modules</summary>
            <label>Shape
                <select wire:model.live="module">
                    @foreach ($moduleNames as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                </select>
            </label>
            <x-pg.options :options="$moduleOptions" path="moduleOptions" :choices="$moduleChoices" />
            <h4>Paint</h4>
            <x-pg.paint :spec="$paint" path="paint" />
        </details>

        <details wire:ignore.self>
            <summary>Background</summary>
            <label class="inline"><input type="checkbox" wire:model.live="transparent"> Transparent</label>
            @unless ($transparent)
                <x-pg.paint :spec="$background" path="background" />
            @endunless
        </details>

        <details wire:ignore.self>
            <summary>Eyes</summary>
            <div class="row">
                <label class="inline"><input type="radio" value="preset" wire:model.live="eyeMode"> A preset</label>
                <label class="inline"><input type="radio" value="parts" wire:model.live="eyeMode"> Outer and inner</label>
            </div>
            @if ($eyeMode === 'preset')
                <label>Preset
                    <select wire:model.live="eye">
                        @foreach ($eyeNames as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                    </select>
                </label>
            @else
                <label>Outer
                    <select wire:model.live="outer">
                        @foreach ($outerNames as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                    </select>
                </label>
                <x-pg.options :options="$outerOptions" path="outerOptions" :choices="$outerChoices" />
                <label>Inner
                    <select wire:model.live="inner">
                        @foreach ($innerNames as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                    </select>
                </label>
                <x-pg.options :options="$innerOptions" path="innerOptions" :choices="$innerChoices" />
            @endif
            <label class="inline"><input type="checkbox" wire:model.live="mirrored"> Mirrored (top-right and bottom-left turned to point out)</label>

            @foreach ($corners as $corner => $settings)
                <details class="layer" wire:ignore.self wire:key="corner-{{ $corner }}">
                    <summary><span>{{ $corner }} <span class="hint">{{ collect([$settings['outer'], $settings['inner'], (float) $settings['rotate'] ? $settings['rotate'].'°' : '', $settings['outerPaint'], $settings['innerPaint']])->filter()->implode(', ') ?: 'as above' }}</span></span></summary>
                    <div class="row">
                        <label>Outer
                            <select wire:model.live="corners.{{ $corner }}.outer">
                                <option value="">as above</option>
                                @foreach ($outerNames as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                            </select>
                        </label>
                        <label>Inner
                            <select wire:model.live="corners.{{ $corner }}.inner">
                                <option value="">as above</option>
                                @foreach ($innerNames as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                            </select>
                        </label>
                    </div>
                    <label>Rotate <input type="number" step="15" wire:model.live.debounce.300ms="corners.{{ $corner }}.rotate"></label>
                    <x-pg.color :path="'corners.'.$corner.'.outerPaint'" label="Outer paint" optional />
                    <x-pg.color :path="'corners.'.$corner.'.innerPaint'" label="Inner paint" optional />
                </details>
            @endforeach
        </details>

        <details open wire:ignore.self>
            <summary>Filters</summary>
            <p class="hint">Run in order, each on what the last made. Every filter can be limited to some roles, a circle, or (eye filters) some corners.</p>
            @foreach ($filters as $index => $filter)
                <div class="filter" wire:key="filter-{{ $index }}-{{ $filter['name'] }}">
                    <div class="filter-head">
                        <b>{{ $filter['name'] }}</b>
                        <span>
                            <button type="button" wire:click="moveFilter({{ $index }}, -1)" aria-label="Move up">↑</button>
                            <button type="button" wire:click="moveFilter({{ $index }}, 1)" aria-label="Move down">↓</button>
                            <button type="button" wire:click="removeFilter({{ $index }})" aria-label="Remove">✕</button>
                        </span>
                    </div>
                    @foreach ($filterFields[$index] as $name => $field)
                        @switch ($field['kind'])
                            @case ('bool')
                                <label class="inline"><input type="checkbox" wire:model.live="filters.{{ $index }}.options.{{ $name }}"> {{ $name }}</label>
                                @break
                            @case ('property')
                                <label>{{ $name }}
                                    <select wire:model.live="filters.{{ $index }}.options.{{ $name }}">
                                        @foreach ($properties as $property)<option value="{{ $property }}">{{ $property }}</option>@endforeach
                                    </select>
                                </label>
                                @break
                            @case ('gradient')
                                <h4>{{ $name }}</h4>
                                <x-pg.layer :spec="$filter['gradient']" :path="'filters.'.$index.'.gradient'" :kinds="['linear' => 'Linear gradient', 'radial' => 'Radial gradient', 'conic' => 'Conic gradient', 'diamond' => 'Diamond gradient', 'mesh' => 'Mesh gradient']" />
                                @break
                            @case ('color')
                                <x-pg.color :path="'filters.'.$index.'.options.'.$name" :label="$name" optional />
                                @break
                            @case ('list')
                                <label>{{ $name }} (comma-separated) <input type="text" wire:model.live.debounce.400ms="filters.{{ $index }}.options.{{ $name }}"></label>
                                @break
                            @default
                                <label>{{ $name }} <input type="number" step="{{ $field['kind'] === 'int' ? 1 : 0.05 }}" wire:model.live.debounce.300ms="filters.{{ $index }}.options.{{ $name }}"></label>
                        @endswitch
                    @endforeach

                    @if ($eyeFilters[$index])
                        <div class="chips">
                            @foreach (\ExeQue\Qr\Rendering\Enums\Corner::cases() as $corner)
                                <label class="inline"><input type="checkbox" value="{{ $corner->value }}" wire:model.live="filters.{{ $index }}.corners"> {{ $corner->value }}</label>
                            @endforeach
                        </div>
                    @else
                        <div class="chips">
                            @foreach ($roles as $role)
                                <label class="inline"><input type="checkbox" value="{{ $role }}" wire:model.live="filters.{{ $index }}.roles"> {{ $role }}</label>
                            @endforeach
                        </div>
                        <label class="inline"><input type="checkbox" wire:model.live="filters.{{ $index }}.region"> Only inside a circle</label>
                        @if ($filter['region'])
                            <div class="row three">
                                <label>x <input type="number" step="0.5" wire:model.live.debounce.300ms="filters.{{ $index }}.regionX"></label>
                                <label>y <input type="number" step="0.5" wire:model.live.debounce.300ms="filters.{{ $index }}.regionY"></label>
                                <label>radius <input type="number" step="0.5" wire:model.live.debounce.300ms="filters.{{ $index }}.regionRadius"></label>
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach
            <div class="row">
                <select wire:model="addFilter">
                    @foreach ($filterNames as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                </select>
                <button type="button" wire:click="addSelectedFilter">Add filter</button>
            </div>
        </details>

        <details wire:ignore.self>
            <summary>Logo</summary>
            <label>Image
                <select wire:model.live="logo">
                    <option value="">None</option>
                    @foreach ($logos as $file)
                        <option value="{{ $file }}">{{ $file }}</option>
                    @endforeach
                    @if ($upload)
                        <option value="uploaded">Uploaded: {{ $upload->getClientOriginalName() }}</option>
                    @endif
                </select>
            </label>
            <label>Your own (PNG, JPEG, WebP, GIF or SVG, up to 2 MB)
                <input type="file" accept=".png,.jpg,.jpeg,.webp,.gif,.svg" wire:model="upload">
            </label>
            @error('upload')<div class="notice error">{{ $message }}</div>@enderror
            @if ($logo !== '')
                <label>Size: {{ $logoSize }} <input type="range" min="0.05" max="0.4" step="0.01" wire:model.live="logoSize"></label>
                <div class="row">
                    <label>Padding, modules <input type="number" min="0" max="4" wire:model.live="logoPadding"></label>
                    <label>Shape
                        <select wire:model.live="logoShape">
                            @foreach (\ExeQue\Qr\Logos\Enums\LogoShape::cases() as $case)<option value="{{ $case->value }}">{{ $case->value }}</option>@endforeach
                        </select>
                    </label>
                </div>
                <label class="inline"><input type="checkbox" wire:model.live="logoPlated"> A plate behind the logo</label>
                @if ($logoPlated)
                    <x-pg.range path="logoPlateRadius" label="Plate radius" :value="$logoPlateRadius" max="4" step="0.25" />
                    <x-pg.paint :spec="$logoPlate" path="logoPlate" />
                @endif
            @endif
        </details>

        <details wire:ignore.self>
            <summary>Scan safety</summary>
            <label class="inline"><input type="checkbox" wire:model.live="guards"> Scan safety guards</label>
            @if ($guards)
                <label>When contrast or a logo is too much
                    <select wire:model.live="violation">
                        <option value="throw">Throw</option>
                        <option value="warn">Warn</option>
                    </select>
                </label>
                <div class="row">
                    @foreach ($limits as $name => $limit)
                        <label>{{ $name }} <input type="number" step="0.05" placeholder="off" wire:model.live.debounce.400ms="limits.{{ $name }}"></label>
                    @endforeach
                </div>
            @endif
        </details>

        <details wire:ignore.self>
            <summary>Output</summary>
            <div class="row">
                <label>Format
                    <select wire:model.live="format">
                        <option value="svg">SVG</option>
                        <option value="png">PNG</option>
                        <option value="jpeg">JPEG</option>
                        <option value="webp">WebP</option>
                    </select>
                </label>
                @if ($format === 'svg')
                    <label>Precision <input type="number" min="0" max="6" wire:model.live="precision"></label>
                @else
                    <label>Drawn with
                        <select wire:model.live="driver">
                            <option value="auto">auto (Imagick when loaded)</option>
                            <option value="imagick">Imagick</option>
                            <option value="gd">GD</option>
                        </select>
                    </label>
                    <label>Size, pixels <input type="number" min="100" max="2400" step="20" wire:model.live.debounce.400ms="size"></label>
                @endif
            </div>
            @if ($format === 'svg')
                <label class="inline"><input type="checkbox" wire:model.live="currentColor"> currentColor</label>
                <label class="inline"><input type="checkbox" wire:model.live="compact"> Compact path data</label>
            @else
                <div class="row">
                    <label>DPI <input type="number" min="72" placeholder="none" wire:model.live.debounce.400ms="dpi"></label>
                    @if ($format !== 'png')
                        <label>Quality <input type="number" min="1" max="100" wire:model.live.debounce.400ms="quality"></label>
                    @endif
                </div>
            @endif
            <label class="inline"><input type="checkbox" wire:model.live="checkScan"> Check that it scans</label>
        </details>
    </div>

    <div class="stage">
        @if ($error)
            <div class="notice error">{{ $error }}</div>
        @else
            <div class="code" wire:loading.class="loading">
                @if ($code)
                    {!! $code !!}
                @else
                    <img src="{{ $image }}" alt="QR code">
                @endif
            </div>
            <div class="stats">
                <span><b>{{ $modules }}</b> × {{ $modules }} modules</span>
                <span><b>{{ number_format($bytes) }}</b> bytes</span>
                <span><b>{{ $milliseconds }}</b> ms</span>
                @if ($scans !== null)
                    <span class="{{ str_starts_with($scans, 'scans') ? 'good' : 'bad' }}">{{ $scans }}</span>
                @endif
            </div>
            @if ($php)
                <x-pg.code :php="$php" />
            @endif
            @if ($adjustments !== [])
                <div class="notice warning">
                    The scan safety guards stepped in:
                    <ul>@foreach (array_slice(array_count_values($adjustments), 0, 12, true) as $adjustment => $times)<li>{{ $adjustment }}{{ $times > 1 ? " ({$times}×)" : '' }}</li>@endforeach</ul>
                </div>
            @endif
        @endif
    </div>
</div>
