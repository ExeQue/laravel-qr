<?php

declare(strict_types=1);

return [
    'array' => 'Поље :attribute мора бити низ.',
    'unknown_keys' => 'Поље :attribute садржи непознате кључеве: :keys.',
    'numbers' => 'Поље :attribute мора бити број од :min до :max или листа од :count таквих бројева.',
    'preset' => 'Поље :attribute није доступно унапред подешено око.',
    'region' => 'Поље :attribute мора бити област: правоугаоник, заобљени правоугаоник или круг задат бројевима, највише 256 ћелија, или лого.',
    'logo_size' => 'Поље :attribute мора бити од :min до :max или auto.',

    'shape' => [
        'module' => 'Поље :attribute није доступан облик модула.',
        'outer' => 'Поље :attribute није доступно спољашње око.',
        'inner' => 'Поље :attribute није доступно унутрашње око.',
        'entry' => 'Поље :attribute мора бити облик: његов назив или назив и његове опције.',
        'no_options' => 'Поље :attribute може навести облик, али не и његове опције.',
    ],

    'option' => [
        'unknown' => 'Опција :attribute овде није подржана.',
        'range' => 'Поље :attribute мора бити од :min до :max.',
        'choice' => 'Поље :attribute мора бити једна од вредности: :values.',
        'list' => 'Поље :attribute мора бити број или листа од :count бројева.',
        'invalid' => 'Поље :attribute садржи опције које се не могу користити заједно.',
    ],

    'filter' => [
        'entry' => 'Поље :attribute мора бити филтер: назив и његове опције.',
        'name' => 'Поље :attribute није доступан филтер.',
    ],

    'paint' => [
        'required' => 'Поље :attribute мора бити испуна.',
        'invalid' => 'Поље :attribute мора бити боја, градијент или слојеви.',
        'no_colors' => 'Поље :attribute мора бити градијент, а не боја.',
        'no_gradients' => 'Поље :attribute мора бити боја, а не градијент.',
        'no_layers' => 'Поље :attribute не сме имати слојеве.',
        'nested_layers' => 'Поље :attribute садржи слојеве унутар слоја.',
        'color' => 'Поље :attribute није боја.',
        'hex' => 'Поље :attribute мора бити хексадецимална боја, на пример #be123c.',
        'kind' => 'Поље :attribute није доступна врста градијента.',
        'stops' => 'Поље :attribute може имати највише :max боја.',
        'min_stops' => 'Поље :attribute мора имати најмање две тачке боје.',
        'stop_color' => 'Поље :attribute садржи тачку боје која није доступна боја.',
        'gradient' => 'Поље :attribute није градијент: :reason',
        'gradient_keys' => 'Поље :attribute садржи подешавања која градијент не подржава или су погрешног типа.',
        'layers' => 'Поље :attribute мора имати од 1 до :max слојева и ништа друго.',
        'layer' => 'Поље :attribute садржи слој који није испуна са непрозирношћу од 0 до 1.',
    ],

    'scannable' => [
        'contrast' => 'Поље :attribute оставља премало контраста за скенирање: :detail.',
        'logo' => 'Поље :attribute чини лого превеликим за скенирање: :detail.',
        'margin' => 'Поље :attribute је премала маргина за скенирање: :detail.',
        'offset' => 'Поље :attribute помера модуле предалеко за скенирање: :detail.',
        'scale' => 'Поље :attribute мења величину модула преко границе скенирања: :detail.',
        'skew' => 'Поље :attribute превише искошава модуле за скенирање: :detail.',
        'tilt' => 'Поље :attribute превише нагиње модуле за скенирање: :detail.',
        'skip' => 'Поље :attribute изоставља више модула него што скенирање дозвољава: :detail.',
        'outer-eye-scale' => 'Поље :attribute мења величину спољашњих очију преко границе скенирања: :detail.',
        'inner-eye-scale' => 'Поље :attribute мења величину унутрашњих очију преко границе скенирања: :detail.',
        'eye-rotation' => 'Поље :attribute окреће очи толико да скенирање можда неће успети: :detail.',
        'inverted' => 'Поље :attribute чини код :detail.',
    ],
];
