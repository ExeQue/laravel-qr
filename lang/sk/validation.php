<?php

declare(strict_types=1);

return [
    'array' => 'Položka :attribute musí byť pole.',
    'unknown_keys' => 'Položka :attribute obsahuje neznáme kľúče: :keys.',
    'numbers' => 'Položka :attribute musí byť číslo od :min do :max alebo zoznam :count takých čísel.',
    'preset' => 'Položka :attribute nie je dostupná predvoľba očí.',
    'region' => 'Položka :attribute musí byť oblasť: obdĺžnik, zaoblený obdĺžnik alebo kruh zadaný číslami, najviac 256 buniek, alebo logo.',
    'logo_size' => 'Položka :attribute musí byť od :min do :max alebo auto.',

    'shape' => [
        'module' => 'Položka :attribute nie je dostupný tvar modulu.',
        'outer' => 'Položka :attribute nie je dostupné vonkajšie oko.',
        'inner' => 'Položka :attribute nie je dostupné vnútorné oko.',
        'entry' => 'Položka :attribute musí byť tvar: jeho názov alebo názov a jeho možnosti.',
        'no_options' => 'Položka :attribute môže uviesť tvar, ale nie jeho možnosti.',
    ],

    'option' => [
        'unknown' => 'Možnosť :attribute tu nie je podporovaná.',
        'range' => 'Položka :attribute musí byť od :min do :max.',
        'choice' => 'Položka :attribute musí byť jedna z hodnôt: :values.',
        'list' => 'Položka :attribute musí byť číslo alebo zoznam :count čísel.',
        'invalid' => 'Položka :attribute obsahuje možnosti, ktoré nemožno použiť spolu.',
    ],

    'filter' => [
        'entry' => 'Položka :attribute musí byť filter: názov a jeho možnosti.',
        'name' => 'Položka :attribute nie je dostupný filter.',
    ],

    'paint' => [
        'required' => 'Položka :attribute musí byť výplň.',
        'invalid' => 'Položka :attribute musí byť farba, prechod alebo vrstvy.',
        'no_colors' => 'Položka :attribute musí byť prechod, nie farba.',
        'no_gradients' => 'Položka :attribute musí byť farba, nie prechod.',
        'no_layers' => 'Položka :attribute nemôže mať vrstvy.',
        'nested_layers' => 'Položka :attribute obsahuje vrstvy vo vnútri vrstvy.',
        'color' => 'Položka :attribute nie je farba.',
        'hex' => 'Položka :attribute musí byť hexadecimálna farba, napríklad #be123c.',
        'kind' => 'Položka :attribute nie je dostupný typ prechodu.',
        'stops' => 'Položka :attribute môže mať najviac :max farieb.',
        'min_stops' => 'Položka :attribute musí mať aspoň dva farebné body.',
        'stop_color' => 'Položka :attribute obsahuje farebný bod, ktorý nie je dostupná farba.',
        'gradient' => 'Položka :attribute nie je prechod: :reason',
        'gradient_keys' => 'Položka :attribute obsahuje nastavenia, ktoré prechod nepodporuje alebo majú nesprávny typ.',
        'layers' => 'Položka :attribute musí mať od 1 do :max vrstiev a nič iné.',
        'layer' => 'Položka :attribute obsahuje vrstvu, ktorá nie je výplň s nepriehľadnosťou od 0 do 1.',
    ],

    'scannable' => [
        'contrast' => 'Farby sú príliš podobné pozadiu, aby sa kód dal spoľahlivo naskenovať.',
        'logo' => 'Logo je príliš veľké, aby sa kód dal spoľahlivo naskenovať.',
        'margin' => 'Prázdny okraj okolo kódu je príliš úzky, aby sa kód dal naskenovať.',
        'offset' => 'Bodky sú posunuté príliš ďaleko od svojich miest, aby sa kód dal naskenovať.',
        'scale' => 'Bodky sú príliš malé alebo príliš veľké, aby sa kód dal naskenovať.',
        'skew' => 'Bodky sú príliš zošikmené, aby sa kód dal naskenovať.',
        'tilt' => 'Bodky sú príliš otočené, aby sa kód dal naskenovať.',
        'skip' => 'Je vynechaných príliš veľa bodiek, aby sa kód dal naskenovať.',
        'outer-eye-scale' => 'Rohové štvorce sú príliš malé alebo príliš veľké, aby sa kód dal naskenovať.',
        'inner-eye-scale' => 'Stredy rohových štvorcov sú príliš malé alebo príliš veľké, aby sa kód dal naskenovať.',
        'eye-rotation' => 'Rohové štvorce sú otočené tak veľmi, že ich niektoré telefóny neprečítajú.',
        'inverted' => 'Kód je svetlejší ako pozadie, a to niektoré telefóny nedokážu prečítať.',
    ],
];
