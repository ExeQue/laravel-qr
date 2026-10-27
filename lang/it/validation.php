<?php

declare(strict_types=1);

return [
    'array' => 'Il campo :attribute deve essere un array.',
    'unknown_keys' => 'Il campo :attribute contiene chiavi sconosciute: :keys.',
    'numbers' => 'Il campo :attribute deve essere un numero compreso tra :min e :max, oppure un elenco di :count numeri di questo tipo.',
    'preset' => 'Il campo :attribute non è un preset degli occhi consentito.',
    'region' => 'Il campo :attribute deve essere un\'area: un rettangolo, un rettangolo arrotondato o un cerchio definito dai suoi valori, fino a 256 celle, oppure il logo.',
    'logo_size' => 'Il campo :attribute deve essere compreso tra :min e :max, oppure auto.',

    'shape' => [
        'module' => 'Il campo :attribute non è una forma di modulo consentita.',
        'outer' => 'Il campo :attribute non è un occhio esterno consentito.',
        'inner' => 'Il campo :attribute non è un occhio interno consentito.',
        'entry' => 'Il campo :attribute deve essere una forma: il suo nome, oppure il nome e le relative opzioni.',
        'no_options' => 'Il campo :attribute può indicare una forma, ma non le sue opzioni.',
    ],

    'option' => [
        'unknown' => 'L\'opzione :attribute non è supportata qui.',
        'range' => 'Il campo :attribute deve essere compreso tra :min e :max.',
        'choice' => 'Il campo :attribute deve essere uno tra: :values.',
        'list' => 'Il campo :attribute deve essere un numero o un elenco di :count numeri.',
        'invalid' => 'Il campo :attribute contiene opzioni che non possono essere usate insieme.',
    ],

    'filter' => [
        'entry' => 'Il campo :attribute deve essere un filtro: un nome e le relative opzioni.',
        'name' => 'Il campo :attribute non è un filtro consentito.',
    ],

    'paint' => [
        'required' => 'Il campo :attribute deve essere un riempimento.',
        'invalid' => 'Il campo :attribute deve essere un colore, un gradiente o dei livelli.',
        'no_colors' => 'Il campo :attribute deve essere un gradiente, non un colore.',
        'no_gradients' => 'Il campo :attribute deve essere un colore, non un gradiente.',
        'no_layers' => 'Il campo :attribute non può avere livelli.',
        'nested_layers' => 'Il campo :attribute contiene livelli annidati in un altro livello.',
        'color' => 'Il campo :attribute non è un colore.',
        'hex' => 'Il campo :attribute deve essere un colore esadecimale, ad esempio #be123c.',
        'kind' => 'Il campo :attribute non è un tipo di gradiente consentito.',
        'stops' => 'Il campo :attribute può avere al massimo :max colori.',
        'min_stops' => 'Il campo :attribute deve avere almeno due punti di colore.',
        'stop_color' => 'Il campo :attribute contiene un punto di colore con un colore non consentito.',
        'gradient' => 'Il campo :attribute non è un gradiente: :reason',
        'gradient_keys' => 'Il campo :attribute contiene impostazioni che un gradiente non accetta o di tipo errato.',
        'layers' => 'Il campo :attribute deve avere da 1 a :max livelli e nient\'altro.',
        'layer' => 'Il campo :attribute contiene un livello che non è composto da un riempimento e un\'opacità da 0 a 1.',
    ],

    'scannable' => [
        'contrast' => 'Il campo :attribute lascia un contrasto troppo basso per la scansione: :detail.',
        'logo' => 'Il campo :attribute rende il logo troppo grande per la scansione: :detail.',
        'margin' => 'Il campo :attribute è un margine troppo piccolo per la scansione: :detail.',
        'offset' => 'Il campo :attribute sposta i moduli troppo per la scansione: :detail.',
        'scale' => 'Il campo :attribute ridimensiona i moduli oltre il limite leggibile: :detail.',
        'skew' => 'Il campo :attribute distorce i moduli troppo per la scansione: :detail.',
        'tilt' => 'Il campo :attribute inclina i moduli troppo per la scansione: :detail.',
        'skip' => 'Il campo :attribute omette troppi moduli per la scansione: :detail.',
        'outer-eye-scale' => 'Il campo :attribute ridimensiona gli occhi esterni oltre il limite leggibile: :detail.',
        'inner-eye-scale' => 'Il campo :attribute ridimensiona gli occhi interni oltre il limite leggibile: :detail.',
        'eye-rotation' => 'Il campo :attribute ruota gli occhi così tanto che potrebbero non essere leggibili: :detail.',
    ],
];
