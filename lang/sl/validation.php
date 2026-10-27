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
        'contrast' => 'Polje :attribute pušča premalo kontrasta za skeniranje: :detail.',
        'logo' => 'Polje :attribute naredi logotip prevelik za skeniranje: :detail.',
        'margin' => 'Polje :attribute je premajhen rob za skeniranje: :detail.',
        'offset' => 'Polje :attribute premakne module predaleč za skeniranje: :detail.',
        'scale' => 'Polje :attribute spremeni velikost modulov čez mejo skeniranja: :detail.',
        'skew' => 'Polje :attribute preveč popači module za skeniranje: :detail.',
        'tilt' => 'Polje :attribute preveč nagne module za skeniranje: :detail.',
        'skip' => 'Polje :attribute izpusti več modulov, kot dopušča skeniranje: :detail.',
        'outer-eye-scale' => 'Polje :attribute spremeni velikost zunanjih oči čez mejo skeniranja: :detail.',
        'inner-eye-scale' => 'Polje :attribute spremeni velikost notranjih oči čez mejo skeniranja: :detail.',
        'eye-rotation' => 'Polje :attribute zasuka oči tako močno, da skeniranje morda ne bo uspelo: :detail.',
        'inverted' => 'Polje :attribute naredi kodo :detail.',
    ],
];
