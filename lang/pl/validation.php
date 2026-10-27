<?php

declare(strict_types=1);

return [
    'array' => 'Pole :attribute musi być tablicą.',
    'unknown_keys' => 'Pole :attribute zawiera nieznane klucze: :keys.',
    'numbers' => 'Pole :attribute musi być liczbą od :min do :max lub listą :count takich liczb.',
    'preset' => 'Pole :attribute nie jest dostępnym szablonem oczu.',
    'region' => 'Pole :attribute musi być obszarem: prostokątem, zaokrąglonym prostokątem lub okręgiem określonym liczbami, do 256 komórek, albo logo.',
    'logo_size' => 'Pole :attribute musi mieścić się w zakresie od :min do :max lub mieć wartość auto.',

    'shape' => [
        'module' => 'Pole :attribute nie jest dostępnym kształtem modułu.',
        'outer' => 'Pole :attribute nie jest dostępnym okiem zewnętrznym.',
        'inner' => 'Pole :attribute nie jest dostępnym okiem wewnętrznym.',
        'entry' => 'Pole :attribute musi być kształtem: jego nazwą albo nazwą wraz z opcjami.',
        'no_options' => 'Pole :attribute może wskazywać kształt, ale bez jego opcji.',
    ],

    'option' => [
        'unknown' => 'Opcja :attribute nie jest tu obsługiwana.',
        'range' => 'Pole :attribute musi mieścić się w zakresie od :min do :max.',
        'choice' => 'Pole :attribute musi mieć jedną z wartości: :values.',
        'list' => 'Pole :attribute musi być liczbą lub listą :count liczb.',
        'invalid' => 'Pole :attribute zawiera opcje, których nie można używać razem.',
    ],

    'filter' => [
        'entry' => 'Pole :attribute musi być filtrem: nazwą i jego opcjami.',
        'name' => 'Pole :attribute nie jest dostępnym filtrem.',
    ],

    'paint' => [
        'required' => 'Pole :attribute musi być wypełnieniem.',
        'invalid' => 'Pole :attribute musi być kolorem, gradientem lub warstwami.',
        'no_colors' => 'Pole :attribute musi być gradientem, a nie kolorem.',
        'no_gradients' => 'Pole :attribute musi być kolorem, a nie gradientem.',
        'no_layers' => 'Pole :attribute nie może mieć warstw.',
        'nested_layers' => 'Pole :attribute zawiera warstwy wewnątrz innej warstwy.',
        'color' => 'Pole :attribute nie jest kolorem.',
        'hex' => 'Pole :attribute musi być kolorem szesnastkowym, np. #be123c.',
        'kind' => 'Pole :attribute nie jest dostępnym rodzajem gradientu.',
        'stops' => 'Pole :attribute może mieć maksymalnie :max kolorów.',
        'min_stops' => 'Pole :attribute musi mieć co najmniej dwa punkty koloru.',
        'stop_color' => 'Pole :attribute zawiera punkt koloru z niedozwolonym kolorem.',
        'gradient' => 'Pole :attribute nie jest gradientem: :reason',
        'gradient_keys' => 'Pole :attribute zawiera ustawienia, których gradient nie obsługuje lub które mają nieprawidłowy typ.',
        'layers' => 'Pole :attribute musi zawierać od 1 do :max warstw i nic więcej.',
        'layer' => 'Pole :attribute zawiera warstwę, która nie składa się z wypełnienia i krycia od 0 do 1.',
    ],

    'scannable' => [
        'contrast' => 'Kolory są zbyt podobne do tła, żeby kod dało się niezawodnie zeskanować.',
        'logo' => 'Logo jest zbyt duże, żeby kod dało się niezawodnie zeskanować.',
        'margin' => 'Pusty margines wokół kodu jest zbyt wąski, żeby kod dało się zeskanować.',
        'offset' => 'Kropki są przesunięte zbyt daleko od swoich miejsc, żeby kod dało się zeskanować.',
        'scale' => 'Kropki są zbyt małe lub zbyt duże, żeby kod dało się zeskanować.',
        'skew' => 'Kropki są zbyt pochylone, żeby kod dało się zeskanować.',
        'tilt' => 'Kropki są zbyt mocno obrócone, żeby kod dało się zeskanować.',
        'skip' => 'Pominięto zbyt wiele kropek, żeby kod dało się zeskanować.',
        'outer-eye-scale' => 'Kwadraty w rogach są zbyt małe lub zbyt duże, żeby kod dało się zeskanować.',
        'inner-eye-scale' => 'Środki kwadratów w rogach są zbyt małe lub zbyt duże, żeby kod dało się zeskanować.',
        'eye-rotation' => 'Kwadraty w rogach są tak mocno obrócone, że niektóre telefony nie mogą ich odczytać.',
        'inverted' => 'Kod jest jaśniejszy od tła, a niektóre telefony nie potrafią go odczytać.',
    ],
];
