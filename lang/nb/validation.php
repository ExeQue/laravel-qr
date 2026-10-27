<?php

declare(strict_types=1);

return [
    'array' => 'Feltet :attribute må være en matrise.',
    'unknown_keys' => 'Feltet :attribute har ukjente nøkler: :keys.',
    'numbers' => 'Feltet :attribute må være et tall fra :min til :max, eller en liste med :count slike tall.',
    'preset' => 'Feltet :attribute er ikke en gyldig øyemal.',
    'region' => 'Feltet :attribute må være et område: et rektangel, et avrundet rektangel eller en sirkel angitt med tall, på opptil 256 celler, eller logoen.',
    'logo_size' => 'Feltet :attribute må være fra :min til :max, eller auto.',

    'shape' => [
        'module' => 'Feltet :attribute er ikke en gyldig modulform.',
        'outer' => 'Feltet :attribute er ikke et gyldig ytre øye.',
        'inner' => 'Feltet :attribute er ikke et gyldig indre øye.',
        'entry' => 'Feltet :attribute må være en form: navnet, eller navnet og alternativene.',
        'no_options' => 'Feltet :attribute kan angi en form, men ikke alternativene for den.',
    ],

    'option' => [
        'unknown' => 'Alternativet :attribute støttes ikke her.',
        'range' => 'Feltet :attribute må være fra :min til :max.',
        'choice' => 'Feltet :attribute må være en av :values.',
        'list' => 'Feltet :attribute må være et tall eller en liste med :count tall.',
        'invalid' => 'Feltet :attribute har alternativer som ikke kan brukes sammen.',
    ],

    'filter' => [
        'entry' => 'Feltet :attribute må være et filter: et navn og alternativene.',
        'name' => 'Feltet :attribute er ikke et gyldig filter.',
    ],

    'paint' => [
        'required' => 'Feltet :attribute må være et fyll.',
        'invalid' => 'Feltet :attribute må være en farge, en gradient eller lag.',
        'no_colors' => 'Feltet :attribute må være en gradient, ikke en farge.',
        'no_gradients' => 'Feltet :attribute må være en farge, ikke en gradient.',
        'no_layers' => 'Feltet :attribute kan ikke ha lag.',
        'nested_layers' => 'Feltet :attribute har lag inne i et annet lag.',
        'color' => 'Feltet :attribute er ikke en farge.',
        'hex' => 'Feltet :attribute må være en heksadesimal farge, for eksempel #be123c.',
        'kind' => 'Feltet :attribute er ikke en gyldig gradienttype.',
        'stops' => 'Feltet :attribute kan ha maks :max farger.',
        'min_stops' => 'Feltet :attribute må ha minst to fargestopp.',
        'stop_color' => 'Feltet :attribute har et fargestopp med en ugyldig farge.',
        'gradient' => 'Feltet :attribute er ikke en gradient: :reason',
        'gradient_keys' => 'Feltet :attribute har innstillinger som en gradient ikke støtter, eller som har feil type.',
        'layers' => 'Feltet :attribute må ha fra 1 til :max lag, og ingenting annet.',
        'layer' => 'Feltet :attribute har et lag som ikke er et fyll med en opasitet fra 0 til 1.',
    ],

    'scannable' => [
        'contrast' => 'Feltet :attribute gir for lav kontrast til at koden kan skannes: :detail.',
        'logo' => 'Feltet :attribute gjør logoen for stor til at koden kan skannes: :detail.',
        'margin' => 'Feltet :attribute gir for liten marg til at koden kan skannes: :detail.',
        'offset' => 'Feltet :attribute flytter modulene for langt til at koden kan skannes: :detail.',
        'scale' => 'Feltet :attribute endrer størrelsen på modulene for mye til at koden kan skannes: :detail.',
        'skew' => 'Feltet :attribute forvrenger modulene for mye til at koden kan skannes: :detail.',
        'tilt' => 'Feltet :attribute vipper modulene for mye til at koden kan skannes: :detail.',
        'skip' => 'Feltet :attribute utelater for mange moduler til at koden kan skannes: :detail.',
        'outer-eye-scale' => 'Feltet :attribute endrer størrelsen på de ytre øynene for mye til at koden kan skannes: :detail.',
        'inner-eye-scale' => 'Feltet :attribute endrer størrelsen på de indre øynene for mye til at koden kan skannes: :detail.',
        'eye-rotation' => 'Feltet :attribute roterer øynene så mye at koden kanskje ikke kan skannes: :detail.',
    ],
];
