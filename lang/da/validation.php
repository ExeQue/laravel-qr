<?php

declare(strict_types=1);

return [
    'array' => 'Feltet :attribute skal være et array.',
    'unknown_keys' => 'Feltet :attribute har ukendte nøgler: :keys.',
    'numbers' => 'Feltet :attribute skal være et tal fra :min til :max eller en liste med :count af dem.',
    'preset' => 'Feltet :attribute er ikke et forudindstillet øje, der kan vælges.',
    'region' => 'Feltet :attribute skal være et område: et rektangel, et afrundet rektangel eller en cirkel angivet med tal, op til 256 celler, eller logoet.',
    'logo_size' => 'Feltet :attribute skal være fra :min til :max eller auto.',

    'shape' => [
        'module' => 'Feltet :attribute er ikke en modulform, der kan vælges.',
        'outer' => 'Feltet :attribute er ikke et ydre øje, der kan vælges.',
        'inner' => 'Feltet :attribute er ikke et indre øje, der kan vælges.',
        'entry' => 'Feltet :attribute skal være en form: dens navn eller dens navn og indstillinger.',
        'no_options' => 'Feltet :attribute må angive en form, men ikke dens indstillinger.',
    ],

    'option' => [
        'unknown' => 'Indstillingen :attribute understøttes ikke her.',
        'range' => 'Feltet :attribute skal være fra :min til :max.',
        'choice' => 'Feltet :attribute skal være en af :values.',
        'list' => 'Feltet :attribute skal være et tal eller en liste med :count tal.',
        'invalid' => 'Feltet :attribute indeholder indstillinger, der ikke kan bruges sammen.',
    ],

    'filter' => [
        'entry' => 'Feltet :attribute skal være et filter: et navn og dets indstillinger.',
        'name' => 'Feltet :attribute er ikke et filter, der kan vælges.',
    ],

    'paint' => [
        'required' => 'Feltet :attribute skal være en udfyldning.',
        'invalid' => 'Feltet :attribute skal være en farve, en gradient eller lag.',
        'no_colors' => 'Feltet :attribute skal være en gradient, ikke en farve.',
        'no_gradients' => 'Feltet :attribute skal være en farve, ikke en gradient.',
        'no_layers' => 'Feltet :attribute må ikke have lag.',
        'nested_layers' => 'Feltet :attribute har lag inden i et lag.',
        'color' => 'Feltet :attribute er ikke en farve.',
        'hex' => 'Feltet :attribute skal være en hex-farve, f.eks. #be123c.',
        'kind' => 'Feltet :attribute er ikke en gradienttype, der kan vælges.',
        'stops' => 'Feltet :attribute må højst have :max farver.',
        'min_stops' => 'Feltet :attribute skal have mindst to farvestop.',
        'stop_color' => 'Feltet :attribute har et farvestop med en farve, der ikke kan vælges.',
        'gradient' => 'Feltet :attribute er ikke en gradient: :reason',
        'gradient_keys' => 'Feltet :attribute har indstillinger, som en gradient ikke understøtter, eller som har den forkerte type.',
        'layers' => 'Feltet :attribute skal have fra 1 til :max lag og intet andet.',
        'layer' => 'Feltet :attribute har et lag, der ikke består af en udfyldning og en opacitet fra 0 til 1.',
    ],

    'scannable' => [
        'contrast' => 'Feltet :attribute giver for lidt kontrast til, at koden kan scannes: :detail.',
        'logo' => 'Feltet :attribute gør logoet for stort til, at koden kan scannes: :detail.',
        'margin' => 'Feltet :attribute er en for lille margen til, at koden kan scannes: :detail.',
        'offset' => 'Feltet :attribute flytter modulerne for langt til, at koden kan scannes: :detail.',
        'scale' => 'Feltet :attribute ændrer modulernes størrelse mere, end koden kan scannes med: :detail.',
        'skew' => 'Feltet :attribute skævvrider modulerne for meget til, at koden kan scannes: :detail.',
        'tilt' => 'Feltet :attribute vipper modulerne for meget til, at koden kan scannes: :detail.',
        'skip' => 'Feltet :attribute udelader for mange moduler til, at koden kan scannes: :detail.',
        'outer-eye-scale' => 'Feltet :attribute ændrer de ydre øjnes størrelse mere, end koden kan scannes med: :detail.',
        'inner-eye-scale' => 'Feltet :attribute ændrer de indre øjnes størrelse mere, end koden kan scannes med: :detail.',
        'eye-rotation' => 'Feltet :attribute drejer øjnene så meget, at koden måske ikke kan scannes: :detail.',
        'inverted' => 'Feltet :attribute gør koden :detail.',
    ],
];
