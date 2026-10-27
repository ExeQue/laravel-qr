<?php

declare(strict_types=1);

return [
    'array' => 'El camp :attribute ha de ser una matriu.',
    'unknown_keys' => 'El camp :attribute té claus desconegudes: :keys.',
    'numbers' => 'El camp :attribute ha de ser un nombre entre :min i :max, o una llista de :count nombres com aquest.',
    'preset' => 'El camp :attribute no és un predefinit d\'ulls disponible.',
    'region' => 'El camp :attribute ha de ser una regió: un rectangle, un rectangle arrodonit o un cercle definit pels seus nombres, de fins a 256 cel·les, o el logotip.',
    'logo_size' => 'El camp :attribute ha d\'estar entre :min i :max, o ser auto.',

    'shape' => [
        'module' => 'El camp :attribute no és una forma de mòdul disponible.',
        'outer' => 'El camp :attribute no és un ull exterior disponible.',
        'inner' => 'El camp :attribute no és un ull interior disponible.',
        'entry' => 'El camp :attribute ha de ser una forma: el seu nom, o el seu nom i les seves opcions.',
        'no_options' => 'El camp :attribute pot indicar una forma, però no les seves opcions.',
    ],

    'option' => [
        'unknown' => 'L\'opció :attribute no s\'admet aquí.',
        'range' => 'El camp :attribute ha d\'estar entre :min i :max.',
        'choice' => 'El camp :attribute ha de ser un dels següents: :values.',
        'list' => 'El camp :attribute ha de ser un nombre o una llista de :count nombres.',
        'invalid' => 'El camp :attribute conté opcions que no es poden fer servir juntes.',
    ],

    'filter' => [
        'entry' => 'El camp :attribute ha de ser un filtre: un nom i les seves opcions.',
        'name' => 'El camp :attribute no és un filtre disponible.',
    ],

    'paint' => [
        'required' => 'El camp :attribute ha de ser un emplenament.',
        'invalid' => 'El camp :attribute ha de ser un color, un degradat o capes.',
        'no_colors' => 'El camp :attribute ha de ser un degradat, no un color.',
        'no_gradients' => 'El camp :attribute ha de ser un color, no un degradat.',
        'no_layers' => 'El camp :attribute no pot tenir capes.',
        'nested_layers' => 'El camp :attribute té capes dins d\'una capa.',
        'color' => 'El camp :attribute no és un color.',
        'hex' => 'El camp :attribute ha de ser un color hexadecimal, com ara #be123c.',
        'kind' => 'El camp :attribute no és un tipus de degradat disponible.',
        'stops' => 'El camp :attribute pot tenir com a màxim :max colors.',
        'min_stops' => 'El camp :attribute ha de tenir almenys dues parades de color.',
        'stop_color' => 'El camp :attribute té una parada de color amb un color no disponible.',
        'gradient' => 'El camp :attribute no és un degradat: :reason',
        'gradient_keys' => 'El camp :attribute té paràmetres que un degradat no admet o que són del tipus incorrecte.',
        'layers' => 'El camp :attribute ha de tenir entre 1 i :max capes, i res més.',
        'layer' => 'El camp :attribute té una capa que no consta d\'un emplenament i una opacitat entre 0 i 1.',
    ],

    'scannable' => [
        'contrast' => 'El camp :attribute deixa massa poc contrast per escanejar: :detail.',
        'logo' => 'El camp :attribute fa el logotip massa gran per escanejar: :detail.',
        'margin' => 'El camp :attribute és un marge massa petit per escanejar: :detail.',
        'offset' => 'El camp :attribute desplaça massa els mòduls per escanejar: :detail.',
        'scale' => 'El camp :attribute canvia la mida dels mòduls més enllà del que es pot escanejar: :detail.',
        'skew' => 'El camp :attribute deforma massa els mòduls per escanejar: :detail.',
        'tilt' => 'El camp :attribute inclina massa els mòduls per escanejar: :detail.',
        'skip' => 'El camp :attribute omet massa mòduls per escanejar: :detail.',
        'outer-eye-scale' => 'El camp :attribute canvia la mida dels ulls exteriors més enllà del que es pot escanejar: :detail.',
        'inner-eye-scale' => 'El camp :attribute canvia la mida dels ulls interiors més enllà del que es pot escanejar: :detail.',
        'eye-rotation' => 'El camp :attribute gira tant els ulls que potser el codi no es pot escanejar: :detail.',
        'inverted' => 'El camp :attribute fa el codi :detail.',
    ],
];
