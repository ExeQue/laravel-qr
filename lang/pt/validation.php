<?php

declare(strict_types=1);

return [
    'array' => 'O campo :attribute deve ser uma matriz.',
    'unknown_keys' => 'O campo :attribute tem chaves desconhecidas: :keys.',
    'numbers' => 'O campo :attribute deve ser um número entre :min e :max, ou uma lista de :count desses números.',
    'preset' => 'O campo :attribute não é uma predefinição de olho disponível.',
    'region' => 'O campo :attribute deve ser uma região: um retângulo, um retângulo arredondado ou um círculo definido pelos seus números, até 256 células, ou o logótipo.',
    'logo_size' => 'O campo :attribute deve estar entre :min e :max, ou ser auto.',

    'shape' => [
        'module' => 'O campo :attribute não é uma forma de módulo disponível.',
        'outer' => 'O campo :attribute não é um olho exterior disponível.',
        'inner' => 'O campo :attribute não é um olho interior disponível.',
        'entry' => 'O campo :attribute deve ser uma forma: o seu nome, ou o nome e as suas opções.',
        'no_options' => 'O campo :attribute pode indicar uma forma, mas não as suas opções.',
    ],

    'option' => [
        'unknown' => 'A opção :attribute não é aceite aqui.',
        'range' => 'O campo :attribute deve estar entre :min e :max.',
        'choice' => 'O campo :attribute deve ser um dos seguintes: :values.',
        'list' => 'O campo :attribute deve ser um número ou uma lista de :count números.',
        'invalid' => 'O campo :attribute contém opções que não podem ser usadas em conjunto.',
    ],

    'filter' => [
        'entry' => 'O campo :attribute deve ser um filtro: um nome e as suas opções.',
        'name' => 'O campo :attribute não é um filtro disponível.',
    ],

    'paint' => [
        'required' => 'O campo :attribute deve ser um preenchimento.',
        'invalid' => 'O campo :attribute deve ser uma cor, um gradiente ou camadas.',
        'no_colors' => 'O campo :attribute deve ser um gradiente, não uma cor.',
        'no_gradients' => 'O campo :attribute deve ser uma cor, não um gradiente.',
        'no_layers' => 'O campo :attribute não pode ter camadas.',
        'nested_layers' => 'O campo :attribute tem camadas dentro de uma camada.',
        'color' => 'O campo :attribute não é uma cor.',
        'hex' => 'O campo :attribute deve ser uma cor hexadecimal, como #be123c.',
        'kind' => 'O campo :attribute não é um tipo de gradiente disponível.',
        'stops' => 'O campo :attribute pode ter no máximo :max cores.',
        'min_stops' => 'O campo :attribute deve ter pelo menos duas paragens de cor.',
        'stop_color' => 'O campo :attribute tem uma paragem de cor que não é uma cor disponível.',
        'gradient' => 'O campo :attribute não é um gradiente: :reason',
        'gradient_keys' => 'O campo :attribute tem definições que um gradiente não aceita ou que são do tipo errado.',
        'layers' => 'O campo :attribute deve ter entre 1 e :max camadas, e nada mais.',
        'layer' => 'O campo :attribute tem uma camada que não é um preenchimento com uma opacidade entre 0 e 1.',
    ],

    'scannable' => [
        'contrast' => 'O campo :attribute deixa contraste insuficiente para a leitura: :detail.',
        'logo' => 'O campo :attribute torna o logótipo demasiado grande para a leitura: :detail.',
        'margin' => 'O campo :attribute é uma margem demasiado pequena para a leitura: :detail.',
        'offset' => 'O campo :attribute desloca demasiado os módulos para a leitura: :detail.',
        'scale' => 'O campo :attribute dimensiona os módulos para além do que permite a leitura: :detail.',
        'skew' => 'O campo :attribute distorce demasiado os módulos para a leitura: :detail.',
        'tilt' => 'O campo :attribute inclina demasiado os módulos para a leitura: :detail.',
        'skip' => 'O campo :attribute omite mais módulos do que permite a leitura: :detail.',
        'outer-eye-scale' => 'O campo :attribute dimensiona os olhos exteriores para além do que permite a leitura: :detail.',
        'inner-eye-scale' => 'O campo :attribute dimensiona os olhos interiores para além do que permite a leitura: :detail.',
        'eye-rotation' => 'O campo :attribute roda tanto os olhos que a leitura pode falhar: :detail.',
    ],
];
