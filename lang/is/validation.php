<?php

declare(strict_types=1);

return [
    'array' => 'Reiturinn :attribute verður að vera fylki.',
    'unknown_keys' => 'Reiturinn :attribute inniheldur óþekkta lykla: :keys.',
    'numbers' => 'Reiturinn :attribute verður að vera tala frá :min til :max, eða listi með :count slíkum tölum.',
    'preset' => 'Reiturinn :attribute er ekki augnforstilling sem hægt er að velja.',
    'region' => 'Reiturinn :attribute verður að vera svæði: rétthyrningur, rúnnaður rétthyrningur eða hringur skilgreindur með tölum, allt að 256 hólf, eða lógóið.',
    'logo_size' => 'Reiturinn :attribute verður að vera frá :min til :max, eða auto.',

    'shape' => [
        'module' => 'Reiturinn :attribute er ekki einingarlögun sem hægt er að velja.',
        'outer' => 'Reiturinn :attribute er ekki ytra auga sem hægt er að velja.',
        'inner' => 'Reiturinn :attribute er ekki innra auga sem hægt er að velja.',
        'entry' => 'Reiturinn :attribute verður að vera lögun: heiti hennar, eða heiti og valkostir.',
        'no_options' => 'Reiturinn :attribute má nefna lögun en ekki gefa upp valkosti hennar.',
    ],

    'option' => [
        'unknown' => 'Valkosturinn :attribute er ekki studdur hér.',
        'range' => 'Reiturinn :attribute verður að vera frá :min til :max.',
        'choice' => 'Reiturinn :attribute verður að vera eitt af eftirfarandi: :values.',
        'list' => 'Reiturinn :attribute verður að vera tala eða listi með :count tölum.',
        'invalid' => 'Reiturinn :attribute inniheldur valkosti sem ekki er hægt að nota saman.',
    ],

    'filter' => [
        'entry' => 'Reiturinn :attribute verður að vera sía: heiti og valkostir hennar.',
        'name' => 'Reiturinn :attribute er ekki sía sem hægt er að velja.',
    ],

    'paint' => [
        'required' => 'Reiturinn :attribute verður að vera fylling.',
        'invalid' => 'Reiturinn :attribute verður að vera litur, litastigull eða lög.',
        'no_colors' => 'Reiturinn :attribute verður að vera litastigull, ekki litur.',
        'no_gradients' => 'Reiturinn :attribute verður að vera litur, ekki litastigull.',
        'no_layers' => 'Reiturinn :attribute má ekki vera í lögum.',
        'nested_layers' => 'Reiturinn :attribute inniheldur lög innan lags.',
        'color' => 'Reiturinn :attribute er ekki litur.',
        'hex' => 'Reiturinn :attribute verður að vera sextándakerfislitur, t.d. #be123c.',
        'kind' => 'Reiturinn :attribute er ekki tegund litastiguls sem hægt er að velja.',
        'stops' => 'Reiturinn :attribute má innihalda að hámarki :max liti.',
        'min_stops' => 'Reiturinn :attribute verður að innihalda að minnsta kosti tvo litapunkta.',
        'stop_color' => 'Reiturinn :attribute inniheldur litapunkt með lit sem ekki er hægt að velja.',
        'gradient' => 'Reiturinn :attribute er ekki litastigull: :reason',
        'gradient_keys' => 'Reiturinn :attribute inniheldur stillingar sem litastigull styður ekki, eða af rangri tegund.',
        'layers' => 'Reiturinn :attribute verður að innihalda 1 til :max lög og ekkert annað.',
        'layer' => 'Reiturinn :attribute inniheldur lag sem er ekki fylling með ógagnsæi frá 0 til 1.',
    ],

    'scannable' => [
        'contrast' => 'Reiturinn :attribute skilur eftir of lítil birtuskil til að skanna: :detail.',
        'logo' => 'Reiturinn :attribute gerir lógóið of stórt til að skanna: :detail.',
        'margin' => 'Reiturinn :attribute gefur of litla spássíu til að skanna: :detail.',
        'offset' => 'Reiturinn :attribute færir einingar of langt til að skanna: :detail.',
        'scale' => 'Reiturinn :attribute breytir stærð eininga meira en skönnun leyfir: :detail.',
        'skew' => 'Reiturinn :attribute skekkir einingar of mikið til að skanna: :detail.',
        'tilt' => 'Reiturinn :attribute hallar einingum of mikið til að skanna: :detail.',
        'skip' => 'Reiturinn :attribute sleppir fleiri einingum en skönnun leyfir: :detail.',
        'outer-eye-scale' => 'Reiturinn :attribute breytir stærð ytri augna meira en skönnun leyfir: :detail.',
        'inner-eye-scale' => 'Reiturinn :attribute breytir stærð innri augna meira en skönnun leyfir: :detail.',
        'eye-rotation' => 'Reiturinn :attribute snýr augunum svo mikið að kóðinn skannast hugsanlega ekki: :detail.',
    ],
];
