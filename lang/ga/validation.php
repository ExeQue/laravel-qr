<?php

declare(strict_types=1);

return [
    'array' => 'Caithfidh an réimse :attribute a bheith ina eagar.',
    'unknown_keys' => 'Tá eochracha anaithnide sa réimse :attribute: :keys.',
    'numbers' => 'Caithfidh an réimse :attribute a bheith ina uimhir idir :min agus :max, nó ina liosta de :count díobh.',
    'preset' => 'Níl an réimse :attribute ina réamhshocrú súile is féidir a roghnú.',
    'region' => 'Caithfidh an réimse :attribute a bheith ina réigiún: dronuilleog, dronuilleog chruinn nó ciorcal de réir a uimhreacha, suas le 256 cill, nó an lógó.',
    'logo_size' => 'Caithfidh an réimse :attribute a bheith idir :min agus :max, nó auto.',

    'shape' => [
        'module' => 'Níl an réimse :attribute ina chruth modúil is féidir a roghnú.',
        'outer' => 'Níl an réimse :attribute ina shúil sheachtrach is féidir a roghnú.',
        'inner' => 'Níl an réimse :attribute ina shúil inmheánach is féidir a roghnú.',
        'entry' => 'Caithfidh an réimse :attribute a bheith ina chruth: a ainm, nó a ainm agus a roghanna.',
        'no_options' => 'Féadann an réimse :attribute cruth a ainmniú, ach gan a roghanna a thabhairt.',
    ],

    'option' => [
        'unknown' => 'Ní ghlactar leis an rogha :attribute anseo.',
        'range' => 'Caithfidh an réimse :attribute a bheith idir :min agus :max.',
        'choice' => 'Caithfidh an réimse :attribute a bheith ar cheann de na luachanna seo: :values.',
        'list' => 'Caithfidh an réimse :attribute a bheith ina uimhir nó ina liosta de :count uimhir.',
        'invalid' => 'Tá roghanna sa réimse :attribute nach féidir a úsáid le chéile.',
    ],

    'filter' => [
        'entry' => 'Caithfidh an réimse :attribute a bheith ina scagaire: ainm agus a roghanna.',
        'name' => 'Níl an réimse :attribute ina scagaire is féidir a roghnú.',
    ],

    'paint' => [
        'required' => 'Caithfidh an réimse :attribute a bheith ina líonadh.',
        'invalid' => 'Caithfidh an réimse :attribute a bheith ina dhath, ina ghrádán nó ina sraitheanna.',
        'no_colors' => 'Caithfidh an réimse :attribute a bheith ina ghrádán, ní ina dhath.',
        'no_gradients' => 'Caithfidh an réimse :attribute a bheith ina dhath, ní ina ghrádán.',
        'no_layers' => 'Ní cheadaítear sraitheanna sa réimse :attribute.',
        'nested_layers' => 'Tá sraitheanna laistigh de shraith sa réimse :attribute.',
        'color' => 'Níl an réimse :attribute ina dhath.',
        'hex' => 'Caithfidh an réimse :attribute a bheith ina dhath heicsidheachúlach, mar shampla #be123c.',
        'kind' => 'Níl an réimse :attribute ina chineál grádáin is féidir a roghnú.',
        'stops' => 'Ní féidir níos mó ná :max dath a bheith sa réimse :attribute.',
        'min_stops' => 'Caithfidh dhá stad datha ar a laghad a bheith sa réimse :attribute.',
        'stop_color' => 'Tá stad datha sa réimse :attribute nach bhfuil ina dhath is féidir a roghnú.',
        'gradient' => 'Níl an réimse :attribute ina ghrádán: :reason',
        'gradient_keys' => 'Tá socruithe sa réimse :attribute nach nglacann grádán leo, nó atá den chineál mícheart.',
        'layers' => 'Caithfidh idir 1 agus :max sraith a bheith sa réimse :attribute, agus gan aon rud eile.',
        'layer' => 'Tá sraith sa réimse :attribute nach bhfuil líonadh agus teimhneacht idir 0 agus 1 aici.',
    ],

    'scannable' => [
        'contrast' => 'Tá na dathanna róchosúil leis an gcúlra le go bhféadfaí an cód a scanadh go hiontaofa.',
        'logo' => 'Tá an lógó rómhór le go bhféadfaí an cód a scanadh go hiontaofa.',
        'margin' => 'Tá an t-imeall folamh timpeall an chóid rócheang le go bhféadfaí an cód a scanadh.',
        'offset' => 'Tá na poncanna bogtha rófhada óna n-áit le go bhféadfaí an cód a scanadh.',
        'scale' => 'Tá na poncanna róbheag nó rómhór le go bhféadfaí an cód a scanadh.',
        'skew' => 'Tá na poncanna rófhiar le go bhféadfaí an cód a scanadh.',
        'tilt' => 'Tá na poncanna casta an iomarca le go bhféadfaí an cód a scanadh.',
        'skip' => 'Fágadh an iomarca poncanna amach le go bhféadfaí an cód a scanadh.',
        'outer-eye-scale' => 'Tá cearnóga na gcúinní róbheag nó rómhór le go bhféadfaí an cód a scanadh.',
        'inner-eye-scale' => 'Tá lár chearnóga na gcúinní róbheag nó rómhór le go bhféadfaí an cód a scanadh.',
        'eye-rotation' => 'Tá cearnóga na gcúinní casta chomh mór sin nach féidir le fóin áirithe iad a léamh.',
        'inverted' => 'Tá an cód níos gile ná a chúlra, agus ní féidir le fóin áirithe é sin a léamh.',
    ],
];
