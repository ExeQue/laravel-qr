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
        'contrast' => 'Litirnir eru of líkir bakgrunninum til að hægt sé að skanna kóðann örugglega.',
        'logo' => 'Lógóið er of stórt til að hægt sé að skanna kóðann örugglega.',
        'margin' => 'Tóma brúnin í kringum kóðann er of mjó til að hægt sé að skanna hann.',
        'offset' => 'Punktarnir eru færðir of langt frá sínum stað til að hægt sé að skanna kóðann.',
        'scale' => 'Punktarnir eru of litlir eða of stórir til að hægt sé að skanna kóðann.',
        'skew' => 'Punktarnir halla of mikið til að hægt sé að skanna kóðann.',
        'tilt' => 'Punktunum er snúið of mikið til að hægt sé að skanna kóðann.',
        'skip' => 'Of mörgum punktum er sleppt til að hægt sé að skanna kóðann.',
        'outer-eye-scale' => 'Hornferningarnir eru of litlir eða of stórir til að hægt sé að skanna kóðann.',
        'inner-eye-scale' => 'Miðjur hornferninganna eru of litlar eða of stórar til að hægt sé að skanna kóðann.',
        'eye-rotation' => 'Hornferningunum er snúið svo mikið að sumir símar geta ekki lesið þá.',
        'inverted' => 'Kóðinn er ljósari en bakgrunnurinn og sumir símar geta ekki lesið hann.',
    ],
];
