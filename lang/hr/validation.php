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
        'contrast' => 'Boje su previše slične pozadini da bi se kod mogao pouzdano skenirati.',
        'logo' => 'Logotip je prevelik da bi se kod mogao pouzdano skenirati.',
        'margin' => 'Prazni rub oko koda preuzak je da bi se kod mogao skenirati.',
        'offset' => 'Točkice su previše pomaknute sa svojih mjesta da bi se kod mogao skenirati.',
        'scale' => 'Točkice su premalene ili prevelike da bi se kod mogao skenirati.',
        'skew' => 'Točkice su previše ukošene da bi se kod mogao skenirati.',
        'tilt' => 'Točkice su previše zakrenute da bi se kod mogao skenirati.',
        'skip' => 'Izostavljeno je previše točkica da bi se kod mogao skenirati.',
        'outer-eye-scale' => 'Kutni kvadrati su premaleni ili preveliki da bi se kod mogao skenirati.',
        'inner-eye-scale' => 'Sredine kutnih kvadrata su premalene ili prevelike da bi se kod mogao skenirati.',
        'eye-rotation' => 'Kutni kvadrati su toliko zakrenuti da ih neki mobiteli ne mogu pročitati.',
        'inverted' => 'Kod je svjetliji od pozadine, a to neki mobiteli ne mogu pročitati.',
    ],
];
