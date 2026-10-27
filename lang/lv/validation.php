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
        'contrast' => 'Lauks :attribute atstāj pārāk mazu kontrastu, tāpēc kodu nevarēs noskenēt: :detail.',
        'logo' => 'Lauks :attribute padara logotipu pārāk lielu, tāpēc kodu nevarēs noskenēt: :detail.',
        'margin' => 'Lauks :attribute nosaka pārāk mazu malu, tāpēc kodu nevarēs noskenēt: :detail.',
        'offset' => 'Lauks :attribute pārvieto moduļus pārāk tālu, tāpēc kodu nevarēs noskenēt: :detail.',
        'scale' => 'Lauks :attribute pārāk maina moduļu izmēru, tāpēc kodu nevarēs noskenēt: :detail.',
        'skew' => 'Lauks :attribute pārāk sašķiebj moduļus, tāpēc kodu nevarēs noskenēt: :detail.',
        'tilt' => 'Lauks :attribute pārāk sasver moduļus, tāpēc kodu nevarēs noskenēt: :detail.',
        'skip' => 'Lauks :attribute izlaiž pārāk daudz moduļu, tāpēc kodu nevarēs noskenēt: :detail.',
        'outer-eye-scale' => 'Lauks :attribute pārāk maina ārējo acu izmēru, tāpēc kodu nevarēs noskenēt: :detail.',
        'inner-eye-scale' => 'Lauks :attribute pārāk maina iekšējo acu izmēru, tāpēc kodu nevarēs noskenēt: :detail.',
        'eye-rotation' => 'Lauks :attribute pagriež acis tik tālu, ka kodu var neizdoties noskenēt: :detail.',
    ],
];
