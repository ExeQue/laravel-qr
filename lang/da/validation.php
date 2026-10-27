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
        'contrast' => 'Farverne ligger for tæt på baggrunden til, at koden kan scannes sikkert.',
        'logo' => 'Logoet er for stort til, at koden kan scannes sikkert.',
        'margin' => 'Den tomme kant om koden er for smal til, at koden kan scannes.',
        'offset' => 'Prikkerne er flyttet for langt fra deres pladser til, at koden kan scannes.',
        'scale' => 'Prikkerne er for små eller for store til, at koden kan scannes.',
        'skew' => 'Prikkerne hælder for meget til, at koden kan scannes.',
        'tilt' => 'Prikkerne er drejet for meget til, at koden kan scannes.',
        'skip' => 'Der er udeladt for mange prikker til, at koden kan scannes.',
        'outer-eye-scale' => 'Hjørnefirkanterne er for små eller for store til, at koden kan scannes.',
        'inner-eye-scale' => 'Midten af hjørnefirkanterne er for lille eller for stor til, at koden kan scannes.',
        'eye-rotation' => 'Hjørnefirkanterne er drejet så meget, at nogle telefoner ikke kan læse dem.',
        'inverted' => 'Koden er lysere end baggrunden, og det kan nogle telefoner ikke læse.',
    ],
];
