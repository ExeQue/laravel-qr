<?php

declare(strict_types=1);

return [
    'array' => 'Il-qasam :attribute għandu jkun array.',
    'unknown_keys' => 'Il-qasam :attribute fih ċwievet mhux magħrufa: :keys.',
    'numbers' => 'Il-qasam :attribute għandu jkun numru minn :min sa :max, jew lista ta\' :count numri bħal dawn.',
    'preset' => 'Il-qasam :attribute mhuwiex preset tal-għajnejn li jista\' jintgħażel.',
    'region' => 'Il-qasam :attribute għandu jkun reġjun: rettangolu, rettangolu b\'kantunieri tondi jew ċirku skont in-numri tiegħu, sa 256 ċellula, jew il-logo.',
    'logo_size' => 'Il-qasam :attribute għandu jkun minn :min sa :max, jew auto.',

    'shape' => [
        'module' => 'Il-qasam :attribute mhuwiex forma ta\' modulu li tista\' tintgħażel.',
        'outer' => 'Il-qasam :attribute mhuwiex għajn ta\' barra li tista\' tintgħażel.',
        'inner' => 'Il-qasam :attribute mhuwiex għajn ta\' ġewwa li tista\' tintgħażel.',
        'entry' => 'Il-qasam :attribute għandu jkun forma: isimha, jew isimha u l-għażliet tagħha.',
        'no_options' => 'Il-qasam :attribute jista\' jsemmi forma, iżda mhux l-għażliet tagħha.',
    ],

    'option' => [
        'unknown' => 'L-għażla :attribute mhijiex aċċettata hawn.',
        'range' => 'Il-qasam :attribute għandu jkun minn :min sa :max.',
        'choice' => 'Il-qasam :attribute għandu jkun wieħed minn: :values.',
        'list' => 'Il-qasam :attribute għandu jkun numru jew lista ta\' :count numri.',
        'invalid' => 'Il-qasam :attribute fih għażliet li ma jistgħux jintużaw flimkien.',
    ],

    'filter' => [
        'entry' => 'Il-qasam :attribute għandu jkun filtru: isem u l-għażliet tiegħu.',
        'name' => 'Il-qasam :attribute mhuwiex filtru li jista\' jintgħażel.',
    ],

    'paint' => [
        'required' => 'Il-qasam :attribute għandu jkun żebgħa.',
        'invalid' => 'Il-qasam :attribute għandu jkun kulur, gradjent jew saffi.',
        'no_colors' => 'Il-qasam :attribute għandu jkun gradjent, mhux kulur.',
        'no_gradients' => 'Il-qasam :attribute għandu jkun kulur, mhux gradjent.',
        'no_layers' => 'Il-qasam :attribute ma jistax ikollu saffi.',
        'nested_layers' => 'Il-qasam :attribute fih saffi ġewwa saff ieħor.',
        'color' => 'Il-qasam :attribute mhuwiex kulur.',
        'hex' => 'Il-qasam :attribute għandu jkun kulur hex, bħal #be123c.',
        'kind' => 'Il-qasam :attribute mhuwiex tip ta\' gradjent li jista\' jintgħażel.',
        'stops' => 'Il-qasam :attribute jista\' jkollu mhux aktar minn :max kuluri.',
        'min_stops' => 'Il-qasam :attribute għandu jkollu mill-inqas żewġ punti tal-kulur.',
        'stop_color' => 'Il-qasam :attribute fih punt tal-kulur b\'kulur li ma jistax jintgħażel.',
        'gradient' => 'Il-qasam :attribute mhuwiex gradjent: :reason',
        'gradient_keys' => 'Il-qasam :attribute fih issettjar li gradjent ma jaċċettax, jew li huwa tat-tip ħażin.',
        'layers' => 'Il-qasam :attribute għandu jkollu minn 1 sa :max saffi, u xejn aktar.',
        'layer' => 'Il-qasam :attribute fih saff li mhuwiex żebgħa b\'opaċità minn 0 sa 1.',
    ],

    'scannable' => [
        'contrast' => 'Il-qasam :attribute iħalli kuntrast baxx wisq biex il-kodiċi jiġi skenjat: :detail.',
        'logo' => 'Il-qasam :attribute jagħmel il-logo kbir wisq biex il-kodiċi jiġi skenjat: :detail.',
        'margin' => 'Il-qasam :attribute huwa marġni żgħir wisq biex il-kodiċi jiġi skenjat: :detail.',
        'offset' => 'Il-qasam :attribute iċaqlaq il-moduli wisq biex il-kodiċi jiġi skenjat: :detail.',
        'scale' => 'Il-qasam :attribute ibiddel id-daqs tal-moduli wisq biex il-kodiċi jiġi skenjat: :detail.',
        'skew' => 'Il-qasam :attribute jgħawweġ il-moduli wisq biex il-kodiċi jiġi skenjat: :detail.',
        'tilt' => 'Il-qasam :attribute imejjel il-moduli wisq biex il-kodiċi jiġi skenjat: :detail.',
        'skip' => 'Il-qasam :attribute iħalli barra wisq moduli biex il-kodiċi jiġi skenjat: :detail.',
        'outer-eye-scale' => 'Il-qasam :attribute ibiddel id-daqs tal-għajnejn ta\' barra wisq biex il-kodiċi jiġi skenjat: :detail.',
        'inner-eye-scale' => 'Il-qasam :attribute ibiddel id-daqs tal-għajnejn ta\' ġewwa wisq biex il-kodiċi jiġi skenjat: :detail.',
        'eye-rotation' => 'Il-qasam :attribute idawwar l-għajnejn tant li l-kodiċi jista\' ma jiġix skenjat: :detail.',
        'inverted' => 'Il-qasam :attribute jagħmel il-kodiċi :detail.',
    ],
];
