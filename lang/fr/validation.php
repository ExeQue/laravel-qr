<?php

declare(strict_types=1);

return [
    'array' => 'Le champ :attribute doit être un tableau.',
    'unknown_keys' => 'Le champ :attribute contient des clés inconnues : :keys.',
    'numbers' => 'Le champ :attribute doit être un nombre compris entre :min et :max, ou une liste de :count de ces nombres.',
    'preset' => 'Le champ :attribute n\'est pas un préréglage d\'œil disponible.',
    'region' => 'Le champ :attribute doit être une région : un rectangle, un rectangle arrondi ou un cercle défini par ses nombres, jusqu\'à 256 cellules, ou le logo.',
    'logo_size' => 'Le champ :attribute doit être compris entre :min et :max, ou auto.',

    'shape' => [
        'module' => 'Le champ :attribute n\'est pas une forme de module disponible.',
        'outer' => 'Le champ :attribute n\'est pas un œil extérieur disponible.',
        'inner' => 'Le champ :attribute n\'est pas un œil intérieur disponible.',
        'entry' => 'Le champ :attribute doit être une forme : son nom, ou son nom et ses options.',
        'no_options' => 'Le champ :attribute peut nommer une forme, mais pas en préciser les options.',
    ],

    'option' => [
        'unknown' => 'L\'option :attribute n\'est pas prise en charge ici.',
        'range' => 'Le champ :attribute doit être compris entre :min et :max.',
        'choice' => 'Le champ :attribute doit être l\'une des valeurs suivantes : :values.',
        'list' => 'Le champ :attribute doit être un nombre ou une liste de :count nombres.',
        'invalid' => 'Le champ :attribute contient des options incompatibles entre elles.',
    ],

    'filter' => [
        'entry' => 'Le champ :attribute doit être un filtre : un nom et ses options.',
        'name' => 'Le champ :attribute n\'est pas un filtre disponible.',
    ],

    'paint' => [
        'required' => 'Le champ :attribute doit être un remplissage.',
        'invalid' => 'Le champ :attribute doit être une couleur, un dégradé ou des calques.',
        'no_colors' => 'Le champ :attribute doit être un dégradé, pas une couleur.',
        'no_gradients' => 'Le champ :attribute doit être une couleur, pas un dégradé.',
        'no_layers' => 'Le champ :attribute ne peut pas comporter de calques.',
        'nested_layers' => 'Le champ :attribute contient des calques imbriqués dans un calque.',
        'color' => 'Le champ :attribute n\'est pas une couleur.',
        'hex' => 'Le champ :attribute doit être une couleur hexadécimale, comme #be123c.',
        'kind' => 'Le champ :attribute n\'est pas un type de dégradé disponible.',
        'stops' => 'Le champ :attribute peut contenir au maximum :max couleurs.',
        'min_stops' => 'Le champ :attribute doit contenir au moins deux points de couleur.',
        'stop_color' => 'Le champ :attribute contient un point de couleur dont la couleur n\'est pas disponible.',
        'gradient' => 'Le champ :attribute n\'est pas un dégradé : :reason',
        'gradient_keys' => 'Le champ :attribute contient des paramètres qu\'un dégradé n\'accepte pas, ou de type incorrect.',
        'layers' => 'Le champ :attribute doit contenir de 1 à :max calques, et rien d\'autre.',
        'layer' => 'Le champ :attribute contient un calque qui n\'est pas composé d\'un remplissage et d\'une opacité de 0 à 1.',
    ],

    'scannable' => [
        'contrast' => 'Le champ :attribute laisse trop peu de contraste pour que le code soit lisible : :detail.',
        'logo' => 'Le champ :attribute rend le logo trop grand pour que le code soit lisible : :detail.',
        'margin' => 'Le champ :attribute est une marge trop petite pour que le code soit lisible : :detail.',
        'offset' => 'Le champ :attribute déplace trop les modules pour que le code soit lisible : :detail.',
        'scale' => 'Le champ :attribute redimensionne les modules au-delà de ce qui reste lisible : :detail.',
        'skew' => 'Le champ :attribute déforme trop les modules pour que le code soit lisible : :detail.',
        'tilt' => 'Le champ :attribute incline trop les modules pour que le code soit lisible : :detail.',
        'skip' => 'Le champ :attribute omet plus de modules que ne le permet la lecture : :detail.',
        'outer-eye-scale' => 'Le champ :attribute redimensionne les yeux extérieurs au-delà de ce qui reste lisible : :detail.',
        'inner-eye-scale' => 'Le champ :attribute redimensionne les yeux intérieurs au-delà de ce qui reste lisible : :detail.',
        'eye-rotation' => 'Le champ :attribute fait tellement pivoter les yeux que le code risque de ne plus être lisible : :detail.',
    ],
];
