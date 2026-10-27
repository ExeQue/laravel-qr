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
        'contrast' => 'Fargene ligger for nær bakgrunnen til at koden kan skannes sikkert.',
        'logo' => 'Logoen er for stor til at koden kan skannes sikkert.',
        'margin' => 'Den tomme kanten rundt koden er for smal til at koden kan skannes.',
        'offset' => 'Prikkene er flyttet for langt fra plassene sine til at koden kan skannes.',
        'scale' => 'Prikkene er for små eller for store til at koden kan skannes.',
        'skew' => 'Prikkene heller for mye til at koden kan skannes.',
        'tilt' => 'Prikkene er dreid for mye til at koden kan skannes.',
        'skip' => 'For mange prikker er utelatt til at koden kan skannes.',
        'outer-eye-scale' => 'Hjørnefirkantene er for små eller for store til at koden kan skannes.',
        'inner-eye-scale' => 'Midten av hjørnefirkantene er for liten eller for stor til at koden kan skannes.',
        'eye-rotation' => 'Hjørnefirkantene er dreid så mye at enkelte telefoner ikke kan lese dem.',
        'inverted' => 'Koden er lysere enn bakgrunnen, og det kan enkelte telefoner ikke lese.',
    ],
];
