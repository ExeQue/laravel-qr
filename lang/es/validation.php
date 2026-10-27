<?php

declare(strict_types=1);

return [
    'array' => 'El campo :attribute debe ser un conjunto.',
    'unknown_keys' => 'El campo :attribute tiene claves desconocidas: :keys.',
    'numbers' => 'El campo :attribute debe ser un número entre :min y :max, o una lista de :count de ellos.',
    'preset' => 'El campo :attribute no es un ajuste predefinido de ojos disponible.',
    'region' => 'El campo :attribute debe ser una región: un rectángulo, un rectángulo redondeado o un círculo definido por sus números, de hasta 256 celdas, o el logotipo.',
    'logo_size' => 'El campo :attribute debe estar entre :min y :max, o ser auto.',

    'shape' => [
        'module' => 'El campo :attribute no es una forma de módulo disponible.',
        'outer' => 'El campo :attribute no es un ojo exterior disponible.',
        'inner' => 'El campo :attribute no es un ojo interior disponible.',
        'entry' => 'El campo :attribute debe ser una forma: su nombre, o su nombre y sus opciones.',
        'no_options' => 'El campo :attribute puede indicar una forma, pero no sus opciones.',
    ],

    'option' => [
        'unknown' => 'La opción :attribute no se admite aquí.',
        'range' => 'El campo :attribute debe estar entre :min y :max.',
        'choice' => 'El campo :attribute debe ser uno de los siguientes: :values.',
        'list' => 'El campo :attribute debe ser un número o una lista de :count números.',
        'invalid' => 'El campo :attribute contiene opciones que no se pueden usar juntas.',
    ],

    'filter' => [
        'entry' => 'El campo :attribute debe ser un filtro: un nombre y sus opciones.',
        'name' => 'El campo :attribute no es un filtro disponible.',
    ],

    'paint' => [
        'required' => 'El campo :attribute debe ser un relleno.',
        'invalid' => 'El campo :attribute debe ser un color, un degradado o capas.',
        'no_colors' => 'El campo :attribute debe ser un degradado, no un color.',
        'no_gradients' => 'El campo :attribute debe ser un color, no un degradado.',
        'no_layers' => 'El campo :attribute no puede tener capas.',
        'nested_layers' => 'El campo :attribute tiene capas dentro de una capa.',
        'color' => 'El campo :attribute no es un color.',
        'hex' => 'El campo :attribute debe ser un color hexadecimal, como #be123c.',
        'kind' => 'El campo :attribute no es un tipo de degradado disponible.',
        'stops' => 'El campo :attribute puede tener como máximo :max colores.',
        'min_stops' => 'El campo :attribute debe tener al menos dos paradas de color.',
        'stop_color' => 'El campo :attribute tiene una parada de color con un color no disponible.',
        'gradient' => 'El campo :attribute no es un degradado: :reason',
        'gradient_keys' => 'El campo :attribute tiene ajustes que un degradado no admite o que son del tipo incorrecto.',
        'layers' => 'El campo :attribute debe tener entre 1 y :max capas, y nada más.',
        'layer' => 'El campo :attribute tiene una capa que no consta de un relleno y una opacidad entre 0 y 1.',
    ],

    'scannable' => [
        'contrast' => 'El campo :attribute deja muy poco contraste para escanear: :detail.',
        'logo' => 'El campo :attribute hace que el logotipo sea demasiado grande para escanear: :detail.',
        'margin' => 'El campo :attribute es un margen demasiado pequeño para escanear: :detail.',
        'offset' => 'El campo :attribute desplaza los módulos demasiado para escanear: :detail.',
        'scale' => 'El campo :attribute cambia el tamaño de los módulos más allá de lo escaneable: :detail.',
        'skew' => 'El campo :attribute deforma los módulos demasiado para escanear: :detail.',
        'tilt' => 'El campo :attribute inclina los módulos demasiado para escanear: :detail.',
        'skip' => 'El campo :attribute omite demasiados módulos para escanear: :detail.',
        'outer-eye-scale' => 'El campo :attribute cambia el tamaño de los ojos exteriores más allá de lo escaneable: :detail.',
        'inner-eye-scale' => 'El campo :attribute cambia el tamaño de los ojos interiores más allá de lo escaneable: :detail.',
        'eye-rotation' => 'El campo :attribute gira tanto los ojos que el código podría no escanearse: :detail.',
        'inverted' => 'El campo :attribute hace que el código sea :detail.',
    ],
];
