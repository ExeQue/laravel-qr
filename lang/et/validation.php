<?php

declare(strict_types=1);

return [
    'array' => 'Väli :attribute peab olema massiiv.',
    'unknown_keys' => 'Väljal :attribute on tundmatud võtmed: :keys.',
    'numbers' => 'Väli :attribute peab olema arv vahemikus :min kuni :max või loend, milles on :count sellist arvu.',
    'preset' => 'Väli :attribute ei ole valitav silma eelseadistus.',
    'region' => 'Väli :attribute peab olema piirkond: arvudega antud ristkülik, ümarate nurkadega ristkülik või ring kuni 256 lahtriga, või logo.',
    'logo_size' => 'Väli :attribute peab olema vahemikus :min kuni :max või auto.',

    'shape' => [
        'module' => 'Väli :attribute ei ole valitav mooduli kuju.',
        'outer' => 'Väli :attribute ei ole valitav välimine silm.',
        'inner' => 'Väli :attribute ei ole valitav sisemine silm.',
        'entry' => 'Väli :attribute peab olema kuju: selle nimi või nimi koos valikutega.',
        'no_options' => 'Väli :attribute võib kuju nimetada, kuid ei tohi anda selle valikuid.',
    ],

    'option' => [
        'unknown' => 'Valikut :attribute siin ei toetata.',
        'range' => 'Väli :attribute peab olema vahemikus :min kuni :max.',
        'choice' => 'Väli :attribute peab olema üks järgmistest: :values.',
        'list' => 'Väli :attribute peab olema arv või :count arvust koosnev loend.',
        'invalid' => 'Väljal :attribute on valikud, mida ei saa koos kasutada.',
    ],

    'filter' => [
        'entry' => 'Väli :attribute peab olema filter: nimi ja selle valikud.',
        'name' => 'Väli :attribute ei ole valitav filter.',
    ],

    'paint' => [
        'required' => 'Väli :attribute peab olema täide.',
        'invalid' => 'Väli :attribute peab olema värv, gradient või kihid.',
        'no_colors' => 'Väli :attribute peab olema gradient, mitte värv.',
        'no_gradients' => 'Väli :attribute peab olema värv, mitte gradient.',
        'no_layers' => 'Väljal :attribute ei tohi olla kihte.',
        'nested_layers' => 'Väljal :attribute on kihi sees kihte.',
        'color' => 'Väli :attribute ei ole värv.',
        'hex' => 'Väli :attribute peab olema HEX-värv, näiteks #be123c.',
        'kind' => 'Väli :attribute ei ole valitav gradiendi tüüp.',
        'stops' => 'Väljal :attribute võib olla kuni :max värvi.',
        'min_stops' => 'Väljal :attribute peab olema vähemalt kaks värvipunkti.',
        'stop_color' => 'Väljal :attribute on värvipunkt, mille värv ei ole valitav.',
        'gradient' => 'Väli :attribute ei ole gradient: :reason',
        'gradient_keys' => 'Väljal :attribute on seadeid, mida gradient ei toeta või mis on vale tüüpi.',
        'layers' => 'Väljal :attribute peab olema 1 kuni :max kihti ja mitte midagi muud.',
        'layer' => 'Väljal :attribute on kiht, mis ei koosne täitest ja läbipaistmatusest vahemikus 0 kuni 1.',
    ],

    'scannable' => [
        'contrast' => 'Väli :attribute jätab skannimiseks liiga vähe kontrasti: :detail.',
        'logo' => 'Väli :attribute muudab logo skannimiseks liiga suureks: :detail.',
        'margin' => 'Väli :attribute annab skannimiseks liiga väikese veerise: :detail.',
        'offset' => 'Väli :attribute nihutab mooduleid skannimiseks liiga palju: :detail.',
        'scale' => 'Väli :attribute muudab moodulite suurust rohkem, kui skannimine lubab: :detail.',
        'skew' => 'Väli :attribute moonutab mooduleid skannimiseks liiga palju: :detail.',
        'tilt' => 'Väli :attribute kallutab mooduleid skannimiseks liiga palju: :detail.',
        'skip' => 'Väli :attribute jätab välja rohkem mooduleid, kui skannimine lubab: :detail.',
        'outer-eye-scale' => 'Väli :attribute muudab välimiste silmade suurust rohkem, kui skannimine lubab: :detail.',
        'inner-eye-scale' => 'Väli :attribute muudab sisemiste silmade suurust rohkem, kui skannimine lubab: :detail.',
        'eye-rotation' => 'Väli :attribute pöörab silmi nii palju, et koodi ei pruugi saada skannida: :detail.',
    ],
];
