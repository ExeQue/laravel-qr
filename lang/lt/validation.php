<?php

declare(strict_types=1);

return [
    'array' => 'Laukas :attribute turi būti masyvas.',
    'unknown_keys' => 'Lauke :attribute yra nežinomų raktų: :keys.',
    'numbers' => 'Laukas :attribute turi būti skaičius nuo :min iki :max arba :count tokių skaičių sąrašas.',
    'preset' => 'Laukas :attribute nėra leidžiamas akių šablonas.',
    'region' => 'Laukas :attribute turi būti sritis: stačiakampis, suapvalintas stačiakampis arba apskritimas, nurodytas skaičiais, iki 256 langelių, arba logotipas.',
    'logo_size' => 'Laukas :attribute turi būti nuo :min iki :max arba auto.',

    'shape' => [
        'module' => 'Laukas :attribute nėra leidžiama modulio forma.',
        'outer' => 'Laukas :attribute nėra leidžiama išorinė akis.',
        'inner' => 'Laukas :attribute nėra leidžiama vidinė akis.',
        'entry' => 'Laukas :attribute turi būti forma: jos pavadinimas arba pavadinimas su parinktimis.',
        'no_options' => 'Laukas :attribute gali nurodyti formą, bet ne jos parinktis.',
    ],

    'option' => [
        'unknown' => 'Parinktis :attribute čia nepalaikoma.',
        'range' => 'Laukas :attribute turi būti nuo :min iki :max.',
        'choice' => 'Laukas :attribute turi būti viena iš šių reikšmių: :values.',
        'list' => 'Laukas :attribute turi būti skaičius arba :count skaičių sąrašas.',
        'invalid' => 'Lauke :attribute yra parinkčių, kurių negalima naudoti kartu.',
    ],

    'filter' => [
        'entry' => 'Laukas :attribute turi būti filtras: pavadinimas ir jo parinktys.',
        'name' => 'Laukas :attribute nėra leidžiamas filtras.',
    ],

    'paint' => [
        'required' => 'Laukas :attribute turi būti užpildas.',
        'invalid' => 'Laukas :attribute turi būti spalva, gradientas arba sluoksniai.',
        'no_colors' => 'Laukas :attribute turi būti gradientas, o ne spalva.',
        'no_gradients' => 'Laukas :attribute turi būti spalva, o ne gradientas.',
        'no_layers' => 'Laukas :attribute negali turėti sluoksnių.',
        'nested_layers' => 'Lauke :attribute yra sluoksnių kito sluoksnio viduje.',
        'color' => 'Laukas :attribute nėra spalva.',
        'hex' => 'Laukas :attribute turi būti šešioliktainė spalva, pvz., #be123c.',
        'kind' => 'Laukas :attribute nėra leidžiamas gradiento tipas.',
        'stops' => 'Laukas :attribute gali turėti ne daugiau kaip :max spalvų.',
        'min_stops' => 'Laukas :attribute turi turėti bent du spalvų taškus.',
        'stop_color' => 'Lauke :attribute yra spalvos taškas su neleidžiama spalva.',
        'gradient' => 'Laukas :attribute nėra gradientas: :reason',
        'gradient_keys' => 'Lauke :attribute yra nustatymų, kurių gradientas nepriima arba kurie yra netinkamo tipo.',
        'layers' => 'Laukas :attribute turi turėti nuo 1 iki :max sluoksnių ir nieko daugiau.',
        'layer' => 'Lauke :attribute yra sluoksnis, kuris nėra užpildas su nepermatomumu nuo 0 iki 1.',
    ],

    'scannable' => [
        'contrast' => 'Laukas :attribute palieka per mažą kontrastą, todėl kodo nepavyks nuskaityti: :detail.',
        'logo' => 'Laukas :attribute padaro logotipą per didelį, todėl kodo nepavyks nuskaityti: :detail.',
        'margin' => 'Laukas :attribute nustato per mažą paraštę, todėl kodo nepavyks nuskaityti: :detail.',
        'offset' => 'Laukas :attribute per toli perstumia modulius, todėl kodo nepavyks nuskaityti: :detail.',
        'scale' => 'Laukas :attribute per daug pakeičia modulių dydį, todėl kodo nepavyks nuskaityti: :detail.',
        'skew' => 'Laukas :attribute per daug iškreipia modulius, todėl kodo nepavyks nuskaityti: :detail.',
        'tilt' => 'Laukas :attribute per daug pakreipia modulius, todėl kodo nepavyks nuskaityti: :detail.',
        'skip' => 'Laukas :attribute praleidžia per daug modulių, todėl kodo nepavyks nuskaityti: :detail.',
        'outer-eye-scale' => 'Laukas :attribute per daug pakeičia išorinių akių dydį, todėl kodo nepavyks nuskaityti: :detail.',
        'inner-eye-scale' => 'Laukas :attribute per daug pakeičia vidinių akių dydį, todėl kodo nepavyks nuskaityti: :detail.',
        'eye-rotation' => 'Laukas :attribute pasuka akis taip stipriai, kad kodo gali nepavykti nuskaityti: :detail.',
    ],
];
