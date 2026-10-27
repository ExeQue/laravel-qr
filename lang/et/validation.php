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
        'contrast' => 'Värvid on taustaga liiga sarnased, et koodi saaks usaldusväärselt skannida.',
        'logo' => 'Logo on liiga suur, et koodi saaks usaldusväärselt skannida.',
        'margin' => 'Tühi äär koodi ümber on liiga kitsas, et koodi saaks skannida.',
        'offset' => 'Täpid on oma kohtadest liiga kaugele nihutatud, et koodi saaks skannida.',
        'scale' => 'Täpid on liiga väikesed või liiga suured, et koodi saaks skannida.',
        'skew' => 'Täpid on liiga viltu, et koodi saaks skannida.',
        'tilt' => 'Täpid on liiga palju pööratud, et koodi saaks skannida.',
        'skip' => 'Liiga palju täppe on välja jäetud, et koodi saaks skannida.',
        'outer-eye-scale' => 'Nurgaruudud on liiga väikesed või liiga suured, et koodi saaks skannida.',
        'inner-eye-scale' => 'Nurgaruutude keskosad on liiga väikesed või liiga suured, et koodi saaks skannida.',
        'eye-rotation' => 'Nurgaruudud on nii palju pööratud, et mõned telefonid ei suuda neid lugeda.',
        'inverted' => 'Kood on taustast heledam ja mõned telefonid ei suuda seda lugeda.',
    ],
];
