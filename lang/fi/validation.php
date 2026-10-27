<?php

declare(strict_types=1);

return [
    'array' => 'Kentän :attribute on oltava taulukko.',
    'unknown_keys' => 'Kentässä :attribute on tuntemattomia avaimia: :keys.',
    'numbers' => 'Kentän :attribute on oltava luku väliltä :min–:max tai luettelo, jossa on :count tällaista lukua.',
    'preset' => 'Kenttä :attribute ei ole valittavissa oleva silmän esiasetus.',
    'region' => 'Kentän :attribute on oltava alue: lukuina annettu suorakulmio, pyöristetty suorakulmio tai ympyrä, enintään 256 solua, tai logo.',
    'logo_size' => 'Kentän :attribute on oltava väliltä :min–:max tai auto.',

    'shape' => [
        'module' => 'Kenttä :attribute ei ole valittavissa oleva moduulin muoto.',
        'outer' => 'Kenttä :attribute ei ole valittavissa oleva ulompi silmä.',
        'inner' => 'Kenttä :attribute ei ole valittavissa oleva sisempi silmä.',
        'entry' => 'Kentän :attribute on oltava muoto: sen nimi tai nimi ja asetukset.',
        'no_options' => 'Kenttä :attribute voi nimetä muodon, mutta ei antaa sen asetuksia.',
    ],

    'option' => [
        'unknown' => 'Asetusta :attribute ei tueta tässä.',
        'range' => 'Kentän :attribute on oltava väliltä :min–:max.',
        'choice' => 'Kentän :attribute on oltava jokin seuraavista: :values.',
        'list' => 'Kentän :attribute on oltava luku tai :count luvun luettelo.',
        'invalid' => 'Kentässä :attribute on asetuksia, joita ei voi käyttää yhdessä.',
    ],

    'filter' => [
        'entry' => 'Kentän :attribute on oltava suodatin: nimi ja sen asetukset.',
        'name' => 'Kenttä :attribute ei ole valittavissa oleva suodatin.',
    ],

    'paint' => [
        'required' => 'Kentän :attribute on oltava täyttö.',
        'invalid' => 'Kentän :attribute on oltava väri, liukuväri tai tasoja.',
        'no_colors' => 'Kentän :attribute on oltava liukuväri, ei väri.',
        'no_gradients' => 'Kentän :attribute on oltava väri, ei liukuväri.',
        'no_layers' => 'Kentässä :attribute ei saa olla tasoja.',
        'nested_layers' => 'Kentässä :attribute on tasoja tason sisällä.',
        'color' => 'Kenttä :attribute ei ole väri.',
        'hex' => 'Kentän :attribute on oltava heksadesimaaliväri, esimerkiksi #be123c.',
        'kind' => 'Kenttä :attribute ei ole valittavissa oleva liukuvärityyppi.',
        'stops' => 'Kentässä :attribute saa olla enintään :max väriä.',
        'min_stops' => 'Kentässä :attribute on oltava vähintään kaksi värikohtaa.',
        'stop_color' => 'Kentässä :attribute on värikohta, jonka väri ei ole valittavissa.',
        'gradient' => 'Kenttä :attribute ei ole liukuväri: :reason',
        'gradient_keys' => 'Kentässä :attribute on asetuksia, joita liukuväri ei tue tai jotka ovat väärää tyyppiä.',
        'layers' => 'Kentässä :attribute on oltava 1–:max tasoa eikä mitään muuta.',
        'layer' => 'Kentässä :attribute on taso, joka ei koostu täytöstä ja peittävyydestä väliltä 0–1.',
    ],

    'scannable' => [
        'contrast' => 'Värit ovat liian lähellä taustaa, jotta koodin voisi skannata luotettavasti.',
        'logo' => 'Logo on liian suuri, jotta koodin voisi skannata luotettavasti.',
        'margin' => 'Koodin ympärillä oleva tyhjä reuna on liian kapea, jotta koodin voisi skannata.',
        'offset' => 'Pisteet on siirretty liian kauas paikoiltaan, jotta koodin voisi skannata.',
        'scale' => 'Pisteet ovat liian pieniä tai liian suuria, jotta koodin voisi skannata.',
        'skew' => 'Pisteet ovat liian vinossa, jotta koodin voisi skannata.',
        'tilt' => 'Pisteitä on käännetty liikaa, jotta koodin voisi skannata.',
        'skip' => 'Liian monta pistettä on jätetty pois, jotta koodin voisi skannata.',
        'outer-eye-scale' => 'Kulmaneliöt ovat liian pieniä tai liian suuria, jotta koodin voisi skannata.',
        'inner-eye-scale' => 'Kulmaneliöiden keskiosat ovat liian pieniä tai liian suuria, jotta koodin voisi skannata.',
        'eye-rotation' => 'Kulmaneliöitä on käännetty niin paljon, etteivät kaikki puhelimet pysty lukemaan niitä.',
        'inverted' => 'Koodi on taustaansa vaaleampi, eivätkä kaikki puhelimet pysty lukemaan sitä.',
    ],
];
