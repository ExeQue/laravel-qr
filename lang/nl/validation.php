<?php

declare(strict_types=1);

return [
    'array' => 'Het veld :attribute moet een array zijn.',
    'unknown_keys' => 'Het veld :attribute bevat onbekende sleutels: :keys.',
    'numbers' => 'Het veld :attribute moet een getal van :min tot :max zijn, of een lijst van :count van zulke getallen.',
    'preset' => 'Het veld :attribute is geen geldige preset voor de ogen.',
    'region' => 'Het veld :attribute moet een gebied zijn: een rechthoek, afgeronde rechthoek of cirkel op basis van getallen, tot 256 cellen, of het logo.',
    'logo_size' => 'Het veld :attribute moet tussen :min en :max liggen, of auto zijn.',

    'shape' => [
        'module' => 'Het veld :attribute is geen geldige modulevorm.',
        'outer' => 'Het veld :attribute is geen geldig buitenoog.',
        'inner' => 'Het veld :attribute is geen geldig binnenoog.',
        'entry' => 'Het veld :attribute moet een vorm zijn: de naam, of de naam met opties.',
        'no_options' => 'Het veld :attribute mag een vorm noemen, maar er geen opties voor opgeven.',
    ],

    'option' => [
        'unknown' => 'De optie :attribute wordt hier niet ondersteund.',
        'range' => 'Het veld :attribute moet tussen :min en :max liggen.',
        'choice' => 'Het veld :attribute moet een van de volgende waarden zijn: :values.',
        'list' => 'Het veld :attribute moet een getal of een lijst van :count getallen zijn.',
        'invalid' => 'Het veld :attribute bevat opties die niet samen gebruikt kunnen worden.',
    ],

    'filter' => [
        'entry' => 'Het veld :attribute moet een filter zijn: een naam met opties.',
        'name' => 'Het veld :attribute is geen geldig filter.',
    ],

    'paint' => [
        'required' => 'Het veld :attribute moet een vulling zijn.',
        'invalid' => 'Het veld :attribute moet een kleur, een kleurverloop of lagen zijn.',
        'no_colors' => 'Het veld :attribute moet een kleurverloop zijn, geen kleur.',
        'no_gradients' => 'Het veld :attribute moet een kleur zijn, geen kleurverloop.',
        'no_layers' => 'Het veld :attribute mag geen lagen hebben.',
        'nested_layers' => 'Het veld :attribute bevat lagen binnen een laag.',
        'color' => 'Het veld :attribute is geen kleur.',
        'hex' => 'Het veld :attribute moet een hexadecimale kleur zijn, zoals #be123c.',
        'kind' => 'Het veld :attribute is geen geldig type kleurverloop.',
        'stops' => 'Het veld :attribute mag maximaal :max kleuren bevatten.',
        'min_stops' => 'Het veld :attribute moet minstens twee kleurstops hebben.',
        'stop_color' => 'Het veld :attribute bevat een kleurstop met een ongeldige kleur.',
        'gradient' => 'Het veld :attribute is geen kleurverloop: :reason',
        'gradient_keys' => 'Het veld :attribute bevat instellingen die een kleurverloop niet ondersteunt, of die van het verkeerde type zijn.',
        'layers' => 'Het veld :attribute moet 1 tot :max lagen bevatten, en verder niets.',
        'layer' => 'Het veld :attribute bevat een laag die geen vulling met een dekking van 0 tot 1 is.',
    ],

    'scannable' => [
        'contrast' => 'Het veld :attribute geeft te weinig contrast om te scannen: :detail.',
        'logo' => 'Het veld :attribute maakt het logo te groot om te scannen: :detail.',
        'margin' => 'Het veld :attribute is een te kleine marge om te scannen: :detail.',
        'offset' => 'Het veld :attribute verschuift modules te ver om te scannen: :detail.',
        'scale' => 'Het veld :attribute schaalt modules te ver om te scannen: :detail.',
        'skew' => 'Het veld :attribute vervormt modules te sterk om te scannen: :detail.',
        'tilt' => 'Het veld :attribute kantelt modules te ver om te scannen: :detail.',
        'skip' => 'Het veld :attribute laat te veel modules weg om te scannen: :detail.',
        'outer-eye-scale' => 'Het veld :attribute schaalt de buitenogen te ver om te scannen: :detail.',
        'inner-eye-scale' => 'Het veld :attribute schaalt de binnenogen te ver om te scannen: :detail.',
        'eye-rotation' => 'Het veld :attribute draait de ogen zo ver dat de code mogelijk niet scant: :detail.',
        'inverted' => 'Het veld :attribute maakt de code :detail.',
    ],
];
