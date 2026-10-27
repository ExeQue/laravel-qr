<?php

declare(strict_types=1);

return [
    'array' => 'Polje :attribute mora biti niz.',
    'unknown_keys' => 'Polje :attribute sadrži nepoznate ključeve: :keys.',
    'numbers' => 'Polje :attribute mora biti broj od :min do :max ili popis od :count takvih brojeva.',
    'preset' => 'Polje :attribute nije predložak oka koji se može odabrati.',
    'region' => 'Polje :attribute mora biti područje: pravokutnik, zaobljeni pravokutnik ili krug zadan brojevima, do 256 ćelija, ili logotip.',
    'logo_size' => 'Polje :attribute mora biti od :min do :max ili auto.',

    'shape' => [
        'module' => 'Polje :attribute nije oblik modula koji se može odabrati.',
        'outer' => 'Polje :attribute nije vanjsko oko koje se može odabrati.',
        'inner' => 'Polje :attribute nije unutarnje oko koje se može odabrati.',
        'entry' => 'Polje :attribute mora biti oblik: njegov naziv ili naziv i opcije.',
        'no_options' => 'Polje :attribute smije navesti oblik, ali ne i njegove opcije.',
    ],

    'option' => [
        'unknown' => 'Opcija :attribute ovdje nije podržana.',
        'range' => 'Polje :attribute mora biti od :min do :max.',
        'choice' => 'Polje :attribute mora biti jedno od sljedećeg: :values.',
        'list' => 'Polje :attribute mora biti broj ili popis od :count brojeva.',
        'invalid' => 'Polje :attribute sadrži opcije koje se ne mogu koristiti zajedno.',
    ],

    'filter' => [
        'entry' => 'Polje :attribute mora biti filtar: naziv i njegove opcije.',
        'name' => 'Polje :attribute nije filtar koji se može odabrati.',
    ],

    'paint' => [
        'required' => 'Polje :attribute mora biti ispuna.',
        'invalid' => 'Polje :attribute mora biti boja, gradijent ili slojevi.',
        'no_colors' => 'Polje :attribute mora biti gradijent, a ne boja.',
        'no_gradients' => 'Polje :attribute mora biti boja, a ne gradijent.',
        'no_layers' => 'Polje :attribute ne smije imati slojeve.',
        'nested_layers' => 'Polje :attribute sadrži slojeve unutar sloja.',
        'color' => 'Polje :attribute nije boja.',
        'hex' => 'Polje :attribute mora biti heksadecimalna boja, npr. #be123c.',
        'kind' => 'Polje :attribute nije vrsta gradijenta koja se može odabrati.',
        'stops' => 'Polje :attribute smije imati najviše :max boja.',
        'min_stops' => 'Polje :attribute mora imati najmanje dvije točke boje.',
        'stop_color' => 'Polje :attribute sadrži točku boje čija se boja ne može odabrati.',
        'gradient' => 'Polje :attribute nije gradijent: :reason',
        'gradient_keys' => 'Polje :attribute sadrži postavke koje gradijent ne prihvaća ili su pogrešne vrste.',
        'layers' => 'Polje :attribute mora imati od 1 do :max slojeva i ništa drugo.',
        'layer' => 'Polje :attribute sadrži sloj koji nije ispuna s neprozirnošću od 0 do 1.',
    ],

    'scannable' => [
        'contrast' => 'Polje :attribute ostavlja premalo kontrasta za skeniranje: :detail.',
        'logo' => 'Polje :attribute čini logotip prevelikim za skeniranje: :detail.',
        'margin' => 'Polje :attribute daje premalu marginu za skeniranje: :detail.',
        'offset' => 'Polje :attribute previše pomiče module za skeniranje: :detail.',
        'scale' => 'Polje :attribute mijenja veličinu modula više nego što skeniranje dopušta: :detail.',
        'skew' => 'Polje :attribute previše iskrivljuje module za skeniranje: :detail.',
        'tilt' => 'Polje :attribute previše naginje module za skeniranje: :detail.',
        'skip' => 'Polje :attribute izostavlja više modula nego što skeniranje dopušta: :detail.',
        'outer-eye-scale' => 'Polje :attribute mijenja veličinu vanjskih očiju više nego što skeniranje dopušta: :detail.',
        'inner-eye-scale' => 'Polje :attribute mijenja veličinu unutarnjih očiju više nego što skeniranje dopušta: :detail.',
        'eye-rotation' => 'Polje :attribute toliko okreće oči da se kod možda neće moći skenirati: :detail.',
    ],
];
