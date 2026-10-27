<?php

declare(strict_types=1);

return [
    'array' => 'Fältet :attribute måste vara en array.',
    'unknown_keys' => 'Fältet :attribute har okända nycklar: :keys.',
    'numbers' => 'Fältet :attribute måste vara ett tal mellan :min och :max, eller en lista med :count sådana tal.',
    'preset' => 'Fältet :attribute är inte en tillgänglig förinställning för ögon.',
    'region' => 'Fältet :attribute måste vara ett område: en rektangel, en rundad rektangel eller en cirkel angiven med sina tal, högst 256 celler, eller logotypen.',
    'logo_size' => 'Fältet :attribute måste vara mellan :min och :max, eller auto.',

    'shape' => [
        'module' => 'Fältet :attribute är inte en tillgänglig modulform.',
        'outer' => 'Fältet :attribute är inte ett tillgängligt yttre öga.',
        'inner' => 'Fältet :attribute är inte ett tillgängligt inre öga.',
        'entry' => 'Fältet :attribute måste vara en form: dess namn, eller dess namn och alternativ.',
        'no_options' => 'Fältet :attribute får ange en form, men inte dess alternativ.',
    ],

    'option' => [
        'unknown' => 'Alternativet :attribute stöds inte här.',
        'range' => 'Fältet :attribute måste vara mellan :min och :max.',
        'choice' => 'Fältet :attribute måste vara ett av: :values.',
        'list' => 'Fältet :attribute måste vara ett tal eller en lista med :count tal.',
        'invalid' => 'Fältet :attribute innehåller alternativ som inte kan användas tillsammans.',
    ],

    'filter' => [
        'entry' => 'Fältet :attribute måste vara ett filter: ett namn och dess alternativ.',
        'name' => 'Fältet :attribute är inte ett tillgängligt filter.',
    ],

    'paint' => [
        'required' => 'Fältet :attribute måste vara en fyllning.',
        'invalid' => 'Fältet :attribute måste vara en färg, en gradient eller lager.',
        'no_colors' => 'Fältet :attribute måste vara en gradient, inte en färg.',
        'no_gradients' => 'Fältet :attribute måste vara en färg, inte en gradient.',
        'no_layers' => 'Fältet :attribute får inte ha lager.',
        'nested_layers' => 'Fältet :attribute har lager inuti ett lager.',
        'color' => 'Fältet :attribute är inte en färg.',
        'hex' => 'Fältet :attribute måste vara en hexfärg, till exempel #be123c.',
        'kind' => 'Fältet :attribute är inte en tillgänglig typ av gradient.',
        'stops' => 'Fältet :attribute får ha högst :max färger.',
        'min_stops' => 'Fältet :attribute måste ha minst två färgstopp.',
        'stop_color' => 'Fältet :attribute har ett färgstopp som inte är en tillgänglig färg.',
        'gradient' => 'Fältet :attribute är inte en gradient: :reason',
        'gradient_keys' => 'Fältet :attribute har inställningar som en gradient inte stöder, eller av fel typ.',
        'layers' => 'Fältet :attribute måste ha mellan 1 och :max lager, och inget annat.',
        'layer' => 'Fältet :attribute har ett lager som inte är en fyllning med en opacitet mellan 0 och 1.',
    ],

    'scannable' => [
        'contrast' => 'Färgerna ligger för nära bakgrunden för att koden ska gå att skanna säkert.',
        'logo' => 'Logotypen är för stor för att koden ska gå att skanna säkert.',
        'margin' => 'Den tomma kanten runt koden är för smal för att koden ska gå att skanna.',
        'offset' => 'Prickarna är flyttade för långt från sina platser för att koden ska gå att skanna.',
        'scale' => 'Prickarna är för små eller för stora för att koden ska gå att skanna.',
        'skew' => 'Prickarna lutar för mycket för att koden ska gå att skanna.',
        'tilt' => 'Prickarna är för mycket vridna för att koden ska gå att skanna.',
        'skip' => 'För många prickar är utelämnade för att koden ska gå att skanna.',
        'outer-eye-scale' => 'Hörnrutorna är för små eller för stora för att koden ska gå att skanna.',
        'inner-eye-scale' => 'Mitten av hörnrutorna är för liten eller för stor för att koden ska gå att skanna.',
        'eye-rotation' => 'Hörnrutorna är så mycket vridna att vissa telefoner inte kan läsa dem.',
        'inverted' => 'Koden är ljusare än bakgrunden, och det kan vissa telefoner inte läsa.',
    ],
];
