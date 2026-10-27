<?php

declare(strict_types=1);

return [
    'array' => 'Polje :attribute mora biti seznam.',
    'unknown_keys' => 'Polje :attribute vsebuje neznane ključe: :keys.',
    'numbers' => 'Polje :attribute mora biti število med :min in :max ali seznam :count takih števil.',
    'preset' => 'Polje :attribute ni razpoložljiva prednastavitev oči.',
    'region' => 'Polje :attribute mora biti območje: pravokotnik, zaobljen pravokotnik ali krog, podan s številkami, največ 256 celic, ali logotip.',
    'logo_size' => 'Polje :attribute mora biti med :min in :max ali auto.',

    'shape' => [
        'module' => 'Polje :attribute ni razpoložljiva oblika modula.',
        'outer' => 'Polje :attribute ni razpoložljivo zunanje oko.',
        'inner' => 'Polje :attribute ni razpoložljivo notranje oko.',
        'entry' => 'Polje :attribute mora biti oblika: njeno ime ali ime in njene možnosti.',
        'no_options' => 'Polje :attribute lahko navede obliko, ne pa tudi njenih možnosti.',
    ],

    'option' => [
        'unknown' => 'Možnost :attribute tukaj ni podprta.',
        'range' => 'Polje :attribute mora biti med :min in :max.',
        'choice' => 'Polje :attribute mora biti ena od vrednosti: :values.',
        'list' => 'Polje :attribute mora biti število ali seznam :count števil.',
        'invalid' => 'Polje :attribute vsebuje možnosti, ki jih ni mogoče uporabiti skupaj.',
    ],

    'filter' => [
        'entry' => 'Polje :attribute mora biti filter: ime in njegove možnosti.',
        'name' => 'Polje :attribute ni razpoložljiv filter.',
    ],

    'paint' => [
        'required' => 'Polje :attribute mora biti polnilo.',
        'invalid' => 'Polje :attribute mora biti barva, preliv ali plasti.',
        'no_colors' => 'Polje :attribute mora biti preliv, ne barva.',
        'no_gradients' => 'Polje :attribute mora biti barva, ne preliv.',
        'no_layers' => 'Polje :attribute ne sme imeti plasti.',
        'nested_layers' => 'Polje :attribute vsebuje plasti znotraj plasti.',
        'color' => 'Polje :attribute ni barva.',
        'hex' => 'Polje :attribute mora biti šestnajstiška barva, na primer #be123c.',
        'kind' => 'Polje :attribute ni razpoložljiva vrsta preliva.',
        'stops' => 'Polje :attribute ima lahko največ :max barv.',
        'min_stops' => 'Polje :attribute mora imeti vsaj dve barvni točki.',
        'stop_color' => 'Polje :attribute vsebuje barvno točko, ki ni razpoložljiva barva.',
        'gradient' => 'Polje :attribute ni preliv: :reason',
        'gradient_keys' => 'Polje :attribute vsebuje nastavitve, ki jih preliv ne podpira ali so napačnega tipa.',
        'layers' => 'Polje :attribute mora imeti od 1 do :max plasti in nič drugega.',
        'layer' => 'Polje :attribute vsebuje plast, ki ni polnilo z motnostjo med 0 in 1.',
    ],

    'scannable' => [
        'contrast' => 'Barve so preveč podobne ozadju, da bi bilo kodo mogoče zanesljivo skenirati.',
        'logo' => 'Logotip je prevelik, da bi bilo kodo mogoče zanesljivo skenirati.',
        'margin' => 'Prazen rob okoli kode je preozek, da bi bilo kodo mogoče skenirati.',
        'offset' => 'Pikice so preveč premaknjene s svojih mest, da bi bilo kodo mogoče skenirati.',
        'scale' => 'Pikice so premajhne ali prevelike, da bi bilo kodo mogoče skenirati.',
        'skew' => 'Pikice so preveč poševne, da bi bilo kodo mogoče skenirati.',
        'tilt' => 'Pikice so preveč zasukane, da bi bilo kodo mogoče skenirati.',
        'skip' => 'Izpuščenih je preveč pikic, da bi bilo kodo mogoče skenirati.',
        'outer-eye-scale' => 'Kotni kvadrati so premajhni ali preveliki, da bi bilo kodo mogoče skenirati.',
        'inner-eye-scale' => 'Sredine kotnih kvadratov so premajhne ali prevelike, da bi bilo kodo mogoče skenirati.',
        'eye-rotation' => 'Kotni kvadrati so tako zasukani, da jih nekateri telefoni ne morejo prebrati.',
        'inverted' => 'Koda je svetlejša od ozadja, česar nekateri telefoni ne znajo prebrati.',
    ],
];
