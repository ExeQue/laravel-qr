<?php

declare(strict_types=1);

return [
    'array' => 'The :attribute field must be an array.',
    'unknown_keys' => 'The :attribute field has unknown keys: :keys.',
    'numbers' => 'The :attribute field must be a number from :min to :max, or a list of :count of them.',
    'preset' => 'The :attribute field is not an eye preset that may be chosen.',
    'region' => 'The :attribute field must be a region: a rect, rounded rect or circle by its numbers, up to 256 cells, or the logo.',
    'logo_size' => 'The :attribute field must be from :min to :max, or auto.',

    'shape' => [
        'module' => 'The :attribute field is not a module shape that may be chosen.',
        'outer' => 'The :attribute field is not an outer eye that may be chosen.',
        'inner' => 'The :attribute field is not an inner eye that may be chosen.',
        'entry' => 'The :attribute field must be a shape: its name, or its name and options.',
        'no_options' => 'The :attribute field may name a shape, but not give its options.',
    ],

    'option' => [
        'unknown' => 'The :attribute option is not one this takes.',
        'range' => 'The :attribute field must be from :min to :max.',
        'choice' => 'The :attribute field must be one of :values.',
        'list' => 'The :attribute field must be a number or a list of :count numbers.',
        'invalid' => 'The :attribute field holds options that cannot be used together.',
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
        'min_stops' => 'The :attribute field must have at least two color stops.',
        'stop_color' => 'The :attribute field has a color stop that is not a color that may be chosen.',
        'gradient' => 'The :attribute field is not a gradient: :reason',
        'gradient_keys' => 'The :attribute field has settings a gradient does not take, or of the wrong type.',
        'layers' => 'The :attribute field must have from 1 to :max layers, and nothing else.',
        'layer' => 'The :attribute field has a layer that is not a paint and an opacity from 0 to 1.',
    ],

    'scannable' => [
        'contrast' => 'The colors are too close to the background for the code to scan reliably.',
        'logo' => 'The logo is too large for the code to scan reliably.',
        'margin' => 'The empty border around the code is too narrow to scan.',
        'offset' => 'The dots are moved too far from their places to scan.',
        'scale' => 'The dots are too small or too large to scan.',
        'skew' => 'The dots are slanted too much to scan.',
        'tilt' => 'The dots are tilted too much to scan.',
        'skip' => 'Too many dots are left out for the code to scan.',
        'outer-eye-scale' => 'The corner squares are too small or too large to scan.',
        'inner-eye-scale' => 'The middles of the corner squares are too small or too large to scan.',
        'eye-rotation' => 'The corner squares are turned so far that some phones cannot read them.',
        'inverted' => 'The code is lighter than its background, which some phones cannot read.',
    ],
];
