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
        'contrast' => 'I colori sono troppo simili allo sfondo per poter scansionare il codice in modo affidabile.',
        'logo' => 'Il logo è troppo grande per poter scansionare il codice in modo affidabile.',
        'margin' => 'Il bordo vuoto intorno al codice è troppo stretto per poterlo scansionare.',
        'offset' => 'I puntini sono troppo lontani dalla loro posizione per poter scansionare il codice.',
        'scale' => 'I puntini sono troppo piccoli o troppo grandi per poter scansionare il codice.',
        'skew' => 'I puntini sono troppo inclinati per poter scansionare il codice.',
        'tilt' => 'I puntini sono troppo ruotati per poter scansionare il codice.',
        'skip' => 'Sono stati tolti troppi puntini per poter scansionare il codice.',
        'outer-eye-scale' => 'I quadrati agli angoli sono troppo piccoli o troppo grandi per poter scansionare il codice.',
        'inner-eye-scale' => 'Il centro dei quadrati agli angoli è troppo piccolo o troppo grande per poter scansionare il codice.',
        'eye-rotation' => 'I quadrati agli angoli sono ruotati così tanto che alcuni telefoni non riescono a leggerli.',
        'inverted' => 'Il codice è più chiaro dello sfondo, e alcuni telefoni non riescono a leggerlo.',
    ],
];
