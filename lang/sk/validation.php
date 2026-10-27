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
        'contrast' => 'Položka :attribute ponecháva príliš nízky kontrast na skenovanie: :detail.',
        'logo' => 'Položka :attribute robí logo príliš veľkým na skenovanie: :detail.',
        'margin' => 'Položka :attribute je príliš malý okraj na skenovanie: :detail.',
        'offset' => 'Položka :attribute posúva moduly príliš ďaleko na skenovanie: :detail.',
        'scale' => 'Položka :attribute mení veľkosť modulov nad rámec skenovateľnosti: :detail.',
        'skew' => 'Položka :attribute skosí moduly príliš na skenovanie: :detail.',
        'tilt' => 'Položka :attribute nakloní moduly príliš na skenovanie: :detail.',
        'skip' => 'Položka :attribute vynecháva viac modulov, než umožňuje skenovanie: :detail.',
        'outer-eye-scale' => 'Položka :attribute mení veľkosť vonkajších očí nad rámec skenovateľnosti: :detail.',
        'inner-eye-scale' => 'Položka :attribute mení veľkosť vnútorných očí nad rámec skenovateľnosti: :detail.',
        'eye-rotation' => 'Položka :attribute otáča oči tak veľmi, že skenovanie nemusí fungovať: :detail.',
        'inverted' => 'Položka :attribute robí kód :detail.',
    ],
];
