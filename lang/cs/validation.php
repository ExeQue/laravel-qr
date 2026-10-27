<?php

declare(strict_types=1);

return [
    'array' => 'Pole :attribute musí být pole.',
    'unknown_keys' => 'Pole :attribute obsahuje neznámé klíče: :keys.',
    'numbers' => 'Pole :attribute musí být číslo od :min do :max, nebo seznam :count takových čísel.',
    'preset' => 'Pole :attribute není dostupná předvolba očí.',
    'region' => 'Pole :attribute musí být oblast: obdélník, zaoblený obdélník nebo kruh zadaný čísly, nejvýše 256 buněk, nebo logo.',
    'logo_size' => 'Pole :attribute musí být od :min do :max, nebo auto.',

    'shape' => [
        'module' => 'Pole :attribute není dostupný tvar modulu.',
        'outer' => 'Pole :attribute není dostupné vnější oko.',
        'inner' => 'Pole :attribute není dostupné vnitřní oko.',
        'entry' => 'Pole :attribute musí být tvar: jeho název, nebo název a možnosti.',
        'no_options' => 'Pole :attribute může uvést tvar, ale ne jeho možnosti.',
    ],

    'option' => [
        'unknown' => 'Možnost :attribute zde není podporována.',
        'range' => 'Pole :attribute musí být od :min do :max.',
        'choice' => 'Pole :attribute musí být jedna z hodnot: :values.',
        'list' => 'Pole :attribute musí být číslo nebo seznam :count čísel.',
        'invalid' => 'Pole :attribute obsahuje možnosti, které nelze použít společně.',
    ],

    'filter' => [
        'entry' => 'Pole :attribute musí být filtr: název a jeho možnosti.',
        'name' => 'Pole :attribute není dostupný filtr.',
    ],

    'paint' => [
        'required' => 'Pole :attribute musí být výplň.',
        'invalid' => 'Pole :attribute musí být barva, přechod nebo vrstvy.',
        'no_colors' => 'Pole :attribute musí být přechod, ne barva.',
        'no_gradients' => 'Pole :attribute musí být barva, ne přechod.',
        'no_layers' => 'Pole :attribute nesmí mít vrstvy.',
        'nested_layers' => 'Pole :attribute obsahuje vrstvy uvnitř vrstvy.',
        'color' => 'Pole :attribute není barva.',
        'hex' => 'Pole :attribute musí být hexadecimální barva, například #be123c.',
        'kind' => 'Pole :attribute není dostupný typ přechodu.',
        'stops' => 'Pole :attribute může mít nejvýše :max barev.',
        'min_stops' => 'Pole :attribute musí mít alespoň dvě barevné zarážky.',
        'stop_color' => 'Pole :attribute obsahuje barevnou zarážku s nedostupnou barvou.',
        'gradient' => 'Pole :attribute není přechod: :reason',
        'gradient_keys' => 'Pole :attribute obsahuje nastavení, která přechod nepodporuje nebo mají nesprávný typ.',
        'layers' => 'Pole :attribute musí mít 1 až :max vrstev a nic jiného.',
        'layer' => 'Pole :attribute obsahuje vrstvu, která nesestává z výplně a krytí od 0 do 1.',
    ],

    'scannable' => [
        'contrast' => 'Barvy jsou příliš podobné pozadí, aby šel kód spolehlivě naskenovat.',
        'logo' => 'Logo je příliš velké, aby šel kód spolehlivě naskenovat.',
        'margin' => 'Prázdný okraj kolem kódu je příliš úzký, aby šel kód naskenovat.',
        'offset' => 'Tečky jsou posunuté příliš daleko od svých míst, aby šel kód naskenovat.',
        'scale' => 'Tečky jsou příliš malé nebo příliš velké, aby šel kód naskenovat.',
        'skew' => 'Tečky jsou příliš zkosené, aby šel kód naskenovat.',
        'tilt' => 'Tečky jsou příliš natočené, aby šel kód naskenovat.',
        'skip' => 'Je vynecháno příliš mnoho teček, aby šel kód naskenovat.',
        'outer-eye-scale' => 'Rohové čtverce jsou příliš malé nebo příliš velké, aby šel kód naskenovat.',
        'inner-eye-scale' => 'Středy rohových čtverců jsou příliš malé nebo příliš velké, aby šel kód naskenovat.',
        'eye-rotation' => 'Rohové čtverce jsou natočené tak moc, že je některé telefony nepřečtou.',
        'inverted' => 'Kód je světlejší než pozadí, a to některé telefony neumí přečíst.',
    ],
];
