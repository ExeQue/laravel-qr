<?php

declare(strict_types=1);

return [
    'array' => 'The :attribute field must be an array.',
    'unknown_keys' => 'The :attribute field has unknown keys: :keys.',
    'unknown_option' => 'The :attribute option is not one this takes.',
    'numbers' => 'The :attribute field must be a number from :min to :max, or a list of :count of them.',
    'preset' => 'The :attribute field is not an eye preset that may be chosen.',
    'region' => 'The :attribute field must be a region: a rect, rounded rect or circle by its numbers, up to 256 cells, or the logo.',
    'logo_size' => 'The :attribute field must be from :min to :max, or auto.',

    'shape' => [
        'module' => 'The :attribute field is not a module shape that may be chosen.',
        'outer' => 'The :attribute field is not an outer eye that may be chosen.',
        'inner' => 'The :attribute field is not an inner eye that may be chosen.',
        'entry' => 'The :attribute field must be a shape: its name, or its name and options.',
    ],

    'filter' => [
        'entry' => 'The :attribute field must be a filter: a name and its options.',
        'name' => 'The :attribute field is not a filter that may be chosen.',
    ],

    'paint' => [
        'required' => 'The :attribute field must be a paint.',
        'invalid' => 'The :attribute field must be a color, a gradient or layers.',
        'no_colors' => 'The :attribute field must be a gradient, not a color.',
        'no_gradients' => 'The :attribute field must be a color, not a gradient.',
        'no_layers' => 'The :attribute field may not be layered.',
        'nested_layers' => 'The :attribute field has layers within a layer.',
        'color' => 'The :attribute field is not a color.',
        'hex' => 'The :attribute field must be a hex color, such as #be123c.',
        'kind' => 'The :attribute field is not a kind of gradient that may be chosen.',
        'stops' => 'The :attribute field may have at most :max colors.',
        'stop_color' => 'The :attribute field has a color stop that is not a color that may be chosen.',
        'gradient' => 'The :attribute field is not a gradient: :reason',
        'gradient_keys' => 'The :attribute field has settings a gradient does not take, or of the wrong type.',
        'layers' => 'The :attribute field must have from 1 to :max layers, and nothing else.',
        'layer' => 'The :attribute field has a layer that is not a paint and an opacity from 0 to 1.',
    ],

    'scannable' => [
        'contrast' => 'The :attribute field leaves too little contrast to scan: :detail.',
        'logo' => 'The :attribute field makes the logo too large to scan: :detail.',
        'margin' => 'The :attribute field is too small a margin to scan: :detail.',
        'offset' => 'The :attribute field moves modules too far to scan: :detail.',
        'scale' => 'The :attribute field sizes modules beyond what scans: :detail.',
        'skew' => 'The :attribute field skews modules too far to scan: :detail.',
        'skip' => 'The :attribute field leaves out more modules than scan: :detail.',
        'outer-eye-scale' => 'The :attribute field sizes the outer eyes beyond what scans: :detail.',
        'inner-eye-scale' => 'The :attribute field sizes the inner eyes beyond what scans: :detail.',
        'eye-rotation' => 'The :attribute field turns the eyes so far they may not scan: :detail.',
    ],
];
