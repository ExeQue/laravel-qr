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
        'contrast' => 'Fágann an réimse :attribute ró-bheagán codarsnachta le scanadh: :detail.',
        'logo' => 'Déanann an réimse :attribute an lógó rómhór le scanadh: :detail.',
        'margin' => 'Is imeall róbheag le scanadh é an réimse :attribute: :detail.',
        'offset' => 'Bogann an réimse :attribute na modúil rófhada le scanadh: :detail.',
        'scale' => 'Athraíonn an réimse :attribute méid na modúl thar an méid is féidir a scanadh: :detail.',
        'skew' => 'Sceabhann an réimse :attribute na modúil an iomarca le scanadh: :detail.',
        'tilt' => 'Claonann an réimse :attribute na modúil an iomarca le scanadh: :detail.',
        'skip' => 'Fágann an réimse :attribute níos mó modúl ar lár ná mar is féidir a scanadh: :detail.',
        'outer-eye-scale' => 'Athraíonn an réimse :attribute méid na súl seachtrach thar an méid is féidir a scanadh: :detail.',
        'inner-eye-scale' => 'Athraíonn an réimse :attribute méid na súl inmheánach thar an méid is féidir a scanadh: :detail.',
        'eye-rotation' => 'Casann an réimse :attribute na súile chomh mór sin go mb\'fhéidir nach scanfar iad: :detail.',
    ],
];
