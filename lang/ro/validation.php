<?php

declare(strict_types=1);

return [
    'array' => 'Câmpul :attribute trebuie să fie un array.',
    'unknown_keys' => 'Câmpul :attribute are chei necunoscute: :keys.',
    'numbers' => 'Câmpul :attribute trebuie să fie un număr între :min și :max sau o listă de :count astfel de numere.',
    'preset' => 'Câmpul :attribute nu este un model de ochi disponibil.',
    'region' => 'Câmpul :attribute trebuie să fie o regiune: un dreptunghi, un dreptunghi rotunjit sau un cerc definit prin numerele sale, de cel mult 256 de celule, sau logoul.',
    'logo_size' => 'Câmpul :attribute trebuie să fie între :min și :max sau auto.',

    'shape' => [
        'module' => 'Câmpul :attribute nu este o formă de modul disponibilă.',
        'outer' => 'Câmpul :attribute nu este un ochi exterior disponibil.',
        'inner' => 'Câmpul :attribute nu este un ochi interior disponibil.',
        'entry' => 'Câmpul :attribute trebuie să fie o formă: numele ei sau numele și opțiunile ei.',
        'no_options' => 'Câmpul :attribute poate numi o formă, dar nu și opțiunile ei.',
    ],

    'option' => [
        'unknown' => 'Opțiunea :attribute nu este acceptată aici.',
        'range' => 'Câmpul :attribute trebuie să fie între :min și :max.',
        'choice' => 'Câmpul :attribute trebuie să fie unul dintre: :values.',
        'list' => 'Câmpul :attribute trebuie să fie un număr sau o listă de :count numere.',
        'invalid' => 'Câmpul :attribute conține opțiuni care nu pot fi folosite împreună.',
    ],

    'filter' => [
        'entry' => 'Câmpul :attribute trebuie să fie un filtru: un nume și opțiunile sale.',
        'name' => 'Câmpul :attribute nu este un filtru disponibil.',
    ],

    'paint' => [
        'required' => 'Câmpul :attribute trebuie să fie o umplere.',
        'invalid' => 'Câmpul :attribute trebuie să fie o culoare, un gradient sau straturi.',
        'no_colors' => 'Câmpul :attribute trebuie să fie un gradient, nu o culoare.',
        'no_gradients' => 'Câmpul :attribute trebuie să fie o culoare, nu un gradient.',
        'no_layers' => 'Câmpul :attribute nu poate avea straturi.',
        'nested_layers' => 'Câmpul :attribute are straturi în interiorul unui strat.',
        'color' => 'Câmpul :attribute nu este o culoare.',
        'hex' => 'Câmpul :attribute trebuie să fie o culoare hexazecimală, de exemplu #be123c.',
        'kind' => 'Câmpul :attribute nu este un tip de gradient disponibil.',
        'stops' => 'Câmpul :attribute poate avea cel mult :max culori.',
        'min_stops' => 'Câmpul :attribute trebuie să aibă cel puțin două puncte de culoare.',
        'stop_color' => 'Câmpul :attribute are un punct de culoare care nu este o culoare disponibilă.',
        'gradient' => 'Câmpul :attribute nu este un gradient: :reason',
        'gradient_keys' => 'Câmpul :attribute are setări pe care un gradient nu le acceptă sau care au tipul greșit.',
        'layers' => 'Câmpul :attribute trebuie să aibă între 1 și :max straturi și nimic altceva.',
        'layer' => 'Câmpul :attribute are un strat care nu este o umplere cu o opacitate între 0 și 1.',
    ],

    'scannable' => [
        'contrast' => 'Câmpul :attribute lasă un contrast prea mic pentru scanare: :detail.',
        'logo' => 'Câmpul :attribute face logoul prea mare pentru scanare: :detail.',
        'margin' => 'Câmpul :attribute este o margine prea mică pentru scanare: :detail.',
        'offset' => 'Câmpul :attribute deplasează modulele prea mult pentru scanare: :detail.',
        'scale' => 'Câmpul :attribute dimensionează modulele peste limita de scanare: :detail.',
        'skew' => 'Câmpul :attribute deformează modulele prea mult pentru scanare: :detail.',
        'tilt' => 'Câmpul :attribute înclină modulele prea mult pentru scanare: :detail.',
        'skip' => 'Câmpul :attribute omite mai multe module decât permite scanarea: :detail.',
        'outer-eye-scale' => 'Câmpul :attribute dimensionează ochii exteriori peste limita de scanare: :detail.',
        'inner-eye-scale' => 'Câmpul :attribute dimensionează ochii interiori peste limita de scanare: :detail.',
        'eye-rotation' => 'Câmpul :attribute rotește ochii atât de mult încât scanarea poate eșua: :detail.',
        'inverted' => 'Câmpul :attribute face codul :detail.',
    ],
];
