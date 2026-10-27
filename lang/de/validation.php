<?php

declare(strict_types=1);

return [
    'array' => 'Das Feld :attribute muss ein Array sein.',
    'unknown_keys' => 'Das Feld :attribute enthält unbekannte Schlüssel: :keys.',
    'numbers' => 'Das Feld :attribute muss eine Zahl von :min bis :max oder eine Liste von :count solchen Zahlen sein.',
    'preset' => 'Das Feld :attribute ist keine wählbare Augen-Vorlage.',
    'region' => 'Das Feld :attribute muss ein Bereich sein: ein Rechteck, ein abgerundetes Rechteck oder ein Kreis über seine Zahlen, bis zu 256 Zellen, oder das Logo.',
    'logo_size' => 'Das Feld :attribute muss zwischen :min und :max liegen oder auto sein.',

    'shape' => [
        'module' => 'Das Feld :attribute ist keine wählbare Modulform.',
        'outer' => 'Das Feld :attribute ist kein wählbares äußeres Auge.',
        'inner' => 'Das Feld :attribute ist kein wählbares inneres Auge.',
        'entry' => 'Das Feld :attribute muss eine Form sein: ihr Name oder ihr Name mit Optionen.',
        'no_options' => 'Das Feld :attribute darf eine Form benennen, aber keine Optionen dafür angeben.',
    ],

    'option' => [
        'unknown' => 'Die Option :attribute wird hier nicht unterstützt.',
        'range' => 'Das Feld :attribute muss zwischen :min und :max liegen.',
        'choice' => 'Das Feld :attribute muss einer der folgenden Werte sein: :values.',
        'list' => 'Das Feld :attribute muss eine Zahl oder eine Liste von :count Zahlen sein.',
        'invalid' => 'Das Feld :attribute enthält Optionen, die nicht kombiniert werden können.',
    ],

    'filter' => [
        'entry' => 'Das Feld :attribute muss ein Filter sein: ein Name und seine Optionen.',
        'name' => 'Das Feld :attribute ist kein wählbarer Filter.',
    ],

    'paint' => [
        'required' => 'Das Feld :attribute muss eine Füllung sein.',
        'invalid' => 'Das Feld :attribute muss eine Farbe, ein Verlauf oder Ebenen sein.',
        'no_colors' => 'Das Feld :attribute muss ein Verlauf sein, keine Farbe.',
        'no_gradients' => 'Das Feld :attribute muss eine Farbe sein, kein Verlauf.',
        'no_layers' => 'Das Feld :attribute darf keine Ebenen haben.',
        'nested_layers' => 'Das Feld :attribute enthält Ebenen innerhalb einer Ebene.',
        'color' => 'Das Feld :attribute ist keine Farbe.',
        'hex' => 'Das Feld :attribute muss eine Hex-Farbe sein, z. B. #be123c.',
        'kind' => 'Das Feld :attribute ist keine wählbare Verlaufsart.',
        'stops' => 'Das Feld :attribute darf höchstens :max Farben haben.',
        'min_stops' => 'Das Feld :attribute muss mindestens zwei Farbstopps haben.',
        'stop_color' => 'Das Feld :attribute enthält einen Farbstopp mit einer nicht wählbaren Farbe.',
        'gradient' => 'Das Feld :attribute ist kein Verlauf: :reason',
        'gradient_keys' => 'Das Feld :attribute enthält Einstellungen, die ein Verlauf nicht unterstützt oder die den falschen Typ haben.',
        'layers' => 'Das Feld :attribute muss 1 bis :max Ebenen enthalten und sonst nichts.',
        'layer' => 'Das Feld :attribute enthält eine Ebene, die nicht aus einer Füllung und einer Deckkraft von 0 bis 1 besteht.',
    ],

    'scannable' => [
        'contrast' => 'Das Feld :attribute lässt zu wenig Kontrast zum Scannen: :detail.',
        'logo' => 'Das Feld :attribute macht das Logo zu groß zum Scannen: :detail.',
        'margin' => 'Das Feld :attribute ist als Rand zu klein zum Scannen: :detail.',
        'offset' => 'Das Feld :attribute verschiebt die Module zu weit zum Scannen: :detail.',
        'scale' => 'Das Feld :attribute skaliert die Module über das Scannbare hinaus: :detail.',
        'skew' => 'Das Feld :attribute verzerrt die Module zu stark zum Scannen: :detail.',
        'tilt' => 'Das Feld :attribute neigt die Module zu stark zum Scannen: :detail.',
        'skip' => 'Das Feld :attribute lässt zu viele Module zum Scannen weg: :detail.',
        'outer-eye-scale' => 'Das Feld :attribute skaliert die äußeren Augen über das Scannbare hinaus: :detail.',
        'inner-eye-scale' => 'Das Feld :attribute skaliert die inneren Augen über das Scannbare hinaus: :detail.',
        'eye-rotation' => 'Das Feld :attribute dreht die Augen so weit, dass der Code eventuell nicht scannbar ist: :detail.',
        'inverted' => 'Das Feld :attribute macht den Code :detail.',
    ],
];
