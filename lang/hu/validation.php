<?php

declare(strict_types=1);

return [
    'array' => 'A(z) :attribute mezőnek tömbnek kell lennie.',
    'unknown_keys' => 'A(z) :attribute mező ismeretlen kulcsokat tartalmaz: :keys.',
    'numbers' => 'A(z) :attribute mezőnek :min és :max közötti számnak, vagy :count ilyen számból álló listának kell lennie.',
    'preset' => 'A(z) :attribute mező nem választható szemsablon.',
    'region' => 'A(z) :attribute mezőnek régiónak kell lennie: számokkal megadott téglalapnak, lekerekített téglalapnak vagy körnek legfeljebb 256 cellával, vagy a logónak.',
    'logo_size' => 'A(z) :attribute mezőnek :min és :max közöttinek vagy auto értékűnek kell lennie.',

    'shape' => [
        'module' => 'A(z) :attribute mező nem választható modulforma.',
        'outer' => 'A(z) :attribute mező nem választható külső szem.',
        'inner' => 'A(z) :attribute mező nem választható belső szem.',
        'entry' => 'A(z) :attribute mezőnek formának kell lennie: a nevének, vagy a nevének és a beállításainak.',
        'no_options' => 'A(z) :attribute mező megnevezhet egy formát, de a beállításait nem adhatja meg.',
    ],

    'option' => [
        'unknown' => 'A(z) :attribute beállítás itt nem támogatott.',
        'range' => 'A(z) :attribute mezőnek :min és :max között kell lennie.',
        'choice' => 'A(z) :attribute mezőnek a következők egyikének kell lennie: :values.',
        'list' => 'A(z) :attribute mezőnek számnak vagy :count számból álló listának kell lennie.',
        'invalid' => 'A(z) :attribute mező olyan beállításokat tartalmaz, amelyek nem használhatók együtt.',
    ],

    'filter' => [
        'entry' => 'A(z) :attribute mezőnek szűrőnek kell lennie: egy névnek és a beállításainak.',
        'name' => 'A(z) :attribute mező nem választható szűrő.',
    ],

    'paint' => [
        'required' => 'A(z) :attribute mezőnek kitöltésnek kell lennie.',
        'invalid' => 'A(z) :attribute mezőnek színnek, színátmenetnek vagy rétegeknek kell lennie.',
        'no_colors' => 'A(z) :attribute mezőnek színátmenetnek kell lennie, nem színnek.',
        'no_gradients' => 'A(z) :attribute mezőnek színnek kell lennie, nem színátmenetnek.',
        'no_layers' => 'A(z) :attribute mező nem lehet rétegzett.',
        'nested_layers' => 'A(z) :attribute mezőben rétegen belüli rétegek vannak.',
        'color' => 'A(z) :attribute mező nem szín.',
        'hex' => 'A(z) :attribute mezőnek hexadecimális színnek kell lennie, például #be123c.',
        'kind' => 'A(z) :attribute mező nem választható színátmenettípus.',
        'stops' => 'A(z) :attribute mező legfeljebb :max színt tartalmazhat.',
        'min_stops' => 'A(z) :attribute mezőnek legalább két színpontot kell tartalmaznia.',
        'stop_color' => 'A(z) :attribute mező olyan színpontot tartalmaz, amelynek a színe nem választható.',
        'gradient' => 'A(z) :attribute mező nem színátmenet: :reason',
        'gradient_keys' => 'A(z) :attribute mező olyan beállításokat tartalmaz, amelyeket a színátmenet nem fogad el, vagy amelyek típusa hibás.',
        'layers' => 'A(z) :attribute mezőnek 1 és :max közötti számú réteget kell tartalmaznia, és semmi mást.',
        'layer' => 'A(z) :attribute mező olyan réteget tartalmaz, amely nem egy kitöltésből és egy 0 és 1 közötti átlátszatlanságból áll.',
    ],

    'scannable' => [
        'contrast' => 'A(z) :attribute mező túl kevés kontrasztot hagy a beolvasáshoz: :detail.',
        'logo' => 'A(z) :attribute mező miatt a logó túl nagy a beolvasáshoz: :detail.',
        'margin' => 'A(z) :attribute mező túl kicsi margót ad a beolvasáshoz: :detail.',
        'offset' => 'A(z) :attribute mező túl messzire mozdítja a modulokat a beolvasáshoz: :detail.',
        'scale' => 'A(z) :attribute mező a beolvashatóság határán túl méretezi a modulokat: :detail.',
        'skew' => 'A(z) :attribute mező túlságosan eltorzítja a modulokat a beolvasáshoz: :detail.',
        'tilt' => 'A(z) :attribute mező túlságosan megdönti a modulokat a beolvasáshoz: :detail.',
        'skip' => 'A(z) :attribute mező több modult hagy ki, mint amennyi mellett a kód még beolvasható: :detail.',
        'outer-eye-scale' => 'A(z) :attribute mező a beolvashatóság határán túl méretezi a külső szemeket: :detail.',
        'inner-eye-scale' => 'A(z) :attribute mező a beolvashatóság határán túl méretezi a belső szemeket: :detail.',
        'eye-rotation' => 'A(z) :attribute mező annyira elforgatja a szemeket, hogy a kód esetleg nem olvasható be: :detail.',
        'inverted' => 'A(z) :attribute mező miatt a kód :detail.',
    ],
];
