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
        'contrast' => 'Pole :attribute daje zbyt niski kontrast, aby kod dało się zeskanować: :detail.',
        'logo' => 'Pole :attribute sprawia, że logo jest zbyt duże, aby kod dało się zeskanować: :detail.',
        'margin' => 'Pole :attribute ustawia zbyt mały margines, aby kod dało się zeskanować: :detail.',
        'offset' => 'Pole :attribute przesuwa moduły zbyt daleko, aby kod dało się zeskanować: :detail.',
        'scale' => 'Pole :attribute zmienia rozmiar modułów zbyt mocno, aby kod dało się zeskanować: :detail.',
        'skew' => 'Pole :attribute zniekształca moduły zbyt mocno, aby kod dało się zeskanować: :detail.',
        'tilt' => 'Pole :attribute przechyla moduły zbyt mocno, aby kod dało się zeskanować: :detail.',
        'skip' => 'Pole :attribute pomija zbyt wiele modułów, aby kod dało się zeskanować: :detail.',
        'outer-eye-scale' => 'Pole :attribute zmienia rozmiar oczu zewnętrznych zbyt mocno, aby kod dało się zeskanować: :detail.',
        'inner-eye-scale' => 'Pole :attribute zmienia rozmiar oczu wewnętrznych zbyt mocno, aby kod dało się zeskanować: :detail.',
        'eye-rotation' => 'Pole :attribute obraca oczy tak mocno, że kodu może nie dać się zeskanować: :detail.',
    ],
];
