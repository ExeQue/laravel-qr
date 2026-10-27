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
        'contrast' => 'A színek túlságosan hasonlítanak a háttérhez ahhoz, hogy a kód megbízhatóan beolvasható legyen.',
        'logo' => 'A logó túl nagy ahhoz, hogy a kód megbízhatóan beolvasható legyen.',
        'margin' => 'A kód körüli üres szegély túl keskeny ahhoz, hogy a kód beolvasható legyen.',
        'offset' => 'A pöttyök túl messzire kerültek a helyüktől ahhoz, hogy a kód beolvasható legyen.',
        'scale' => 'A pöttyök túl kicsik vagy túl nagyok ahhoz, hogy a kód beolvasható legyen.',
        'skew' => 'A pöttyök túlságosan ferdék ahhoz, hogy a kód beolvasható legyen.',
        'tilt' => 'A pöttyök túlságosan el vannak forgatva ahhoz, hogy a kód beolvasható legyen.',
        'skip' => 'Túl sok pötty maradt ki ahhoz, hogy a kód beolvasható legyen.',
        'outer-eye-scale' => 'A saroknégyzetek túl kicsik vagy túl nagyok ahhoz, hogy a kód beolvasható legyen.',
        'inner-eye-scale' => 'A saroknégyzetek közepe túl kicsi vagy túl nagy ahhoz, hogy a kód beolvasható legyen.',
        'eye-rotation' => 'A saroknégyzetek annyira el vannak forgatva, hogy egyes telefonok nem tudják beolvasni őket.',
        'inverted' => 'A kód világosabb a hátterénél, ezt pedig egyes telefonok nem tudják beolvasni.',
    ],
];
