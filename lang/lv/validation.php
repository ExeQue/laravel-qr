<?php

declare(strict_types=1);

return [
    'array' => 'Laukam :attribute jābūt masīvam.',
    'unknown_keys' => 'Laukā :attribute ir nezināmas atslēgas: :keys.',
    'numbers' => 'Laukam :attribute jābūt skaitlim no :min līdz :max vai :count šādu skaitļu sarakstam.',
    'preset' => 'Lauks :attribute nav atļauta acu sagatave.',
    'region' => 'Laukam :attribute jābūt apgabalam: taisnstūrim, noapaļotam taisnstūrim vai aplim, kas norādīts ar skaitļiem, līdz 256 šūnām, vai logotipam.',
    'logo_size' => 'Laukam :attribute jābūt no :min līdz :max vai auto.',

    'shape' => [
        'module' => 'Lauks :attribute nav atļauta moduļa forma.',
        'outer' => 'Lauks :attribute nav atļauta ārējā acs.',
        'inner' => 'Lauks :attribute nav atļauta iekšējā acs.',
        'entry' => 'Laukam :attribute jābūt formai: tās nosaukumam vai nosaukumam ar opcijām.',
        'no_options' => 'Lauks :attribute var norādīt formu, bet ne tās opcijas.',
    ],

    'option' => [
        'unknown' => 'Opcija :attribute šeit netiek atbalstīta.',
        'range' => 'Laukam :attribute jābūt no :min līdz :max.',
        'choice' => 'Laukam :attribute jābūt vienai no šīm vērtībām: :values.',
        'list' => 'Laukam :attribute jābūt skaitlim vai :count skaitļu sarakstam.',
        'invalid' => 'Laukā :attribute ir opcijas, kuras nevar izmantot kopā.',
    ],

    'filter' => [
        'entry' => 'Laukam :attribute jābūt filtram: nosaukumam un tā opcijām.',
        'name' => 'Lauks :attribute nav atļauts filtrs.',
    ],

    'paint' => [
        'required' => 'Laukam :attribute jābūt aizpildījumam.',
        'invalid' => 'Laukam :attribute jābūt krāsai, gradientam vai slāņiem.',
        'no_colors' => 'Laukam :attribute jābūt gradientam, nevis krāsai.',
        'no_gradients' => 'Laukam :attribute jābūt krāsai, nevis gradientam.',
        'no_layers' => 'Laukam :attribute nedrīkst būt slāņu.',
        'nested_layers' => 'Laukā :attribute ir slāņi cita slāņa iekšienē.',
        'color' => 'Lauks :attribute nav krāsa.',
        'hex' => 'Laukam :attribute jābūt heksadecimālai krāsai, piemēram, #be123c.',
        'kind' => 'Lauks :attribute nav atļauts gradienta veids.',
        'stops' => 'Laukā :attribute drīkst būt ne vairāk kā :max krāsas.',
        'min_stops' => 'Laukā :attribute jābūt vismaz diviem krāsu punktiem.',
        'stop_color' => 'Laukā :attribute ir krāsu punkts ar neatļautu krāsu.',
        'gradient' => 'Lauks :attribute nav gradients: :reason',
        'gradient_keys' => 'Laukā :attribute ir iestatījumi, ko gradients nepieņem vai kas ir nepareiza tipa.',
        'layers' => 'Laukā :attribute jābūt no 1 līdz :max slāņiem un nekam citam.',
        'layer' => 'Laukā :attribute ir slānis, kas nav aizpildījums ar necaurredzamību no 0 līdz 1.',
    ],

    'scannable' => [
        'contrast' => 'Krāsas ir pārāk līdzīgas fonam, lai kodu varētu droši noskenēt.',
        'logo' => 'Logotips ir pārāk liels, lai kodu varētu droši noskenēt.',
        'margin' => 'Tukšā mala ap kodu ir pārāk šaura, lai kodu varētu noskenēt.',
        'offset' => 'Punktiņi ir pārvietoti pārāk tālu no savām vietām, lai kodu varētu noskenēt.',
        'scale' => 'Punktiņi ir pārāk mazi vai pārāk lieli, lai kodu varētu noskenēt.',
        'skew' => 'Punktiņi ir pārāk šķībi, lai kodu varētu noskenēt.',
        'tilt' => 'Punktiņi ir pārāk pagriezti, lai kodu varētu noskenēt.',
        'skip' => 'Izlaists pārāk daudz punktiņu, lai kodu varētu noskenēt.',
        'outer-eye-scale' => 'Stūra kvadrāti ir pārāk mazi vai pārāk lieli, lai kodu varētu noskenēt.',
        'inner-eye-scale' => 'Stūra kvadrātu vidusdaļas ir pārāk mazas vai pārāk lielas, lai kodu varētu noskenēt.',
        'eye-rotation' => 'Stūra kvadrāti ir pagriezti tik ļoti, ka daži telefoni tos nevar nolasīt.',
        'inverted' => 'Kods ir gaišāks par fonu, un daži telefoni to nespēj nolasīt.',
    ],
];
