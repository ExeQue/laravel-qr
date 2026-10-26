# Getting started

```php
use ExeQue\Qr\Facades\Qr;

$svg = Qr::url('https://example.com/e/ABCD1234')->svg();

echo $svg;                       // inline <svg>
$svg->save('codes/event.svg');   // to the default disk
```

![A plain QR code](images/styling/default.png)

A code starts with its content and ends with an output format. In between, everything is optional:

```php
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Gradients\LinearGradient;
use ExeQue\Qr\Shapes\Enums\Eye;

Qr::url('https://example.com/e/ABCD1234')
    ->modules('flowing-medium')
    ->eyes(Eye::Rounded)
    ->paint(LinearGradient::make('#be123c', '#1e1b4b')->angle(135))
    ->png(600)
    ->store('codes/event.png');
```

![A styled QR code](images/styling/styled.png)

## Content

`Qr::make()` takes any text. The other starting points build the text for you, escaped the way phones expect:

```php
Qr::make('Any text at all');
Qr::url('https://example.com');
Qr::wifi('Home', 'correct horse battery staple');
Qr::vcard(name: 'Ada Lovelace', phone: '+44 20 7946 0000', email: 'ada@example.com');
Qr::email('hello@example.com', subject: 'Hi');
Qr::sms('+4512345678', 'On my way');
Qr::geo(55.6761, 12.5683);
Qr::event('Launch', starts: now(), ends: now()->addHour(), location: 'Copenhagen');
```

See [Content](content.md) for every option.

## Formats

```php
$code = Qr::url('https://example.com');

$code->svg();        // vector, scales to any size, needs nothing extra
$code->png(600);     // 600 pixels wide
$code->jpeg(600);    // flattened onto the background, or white
$code->webp(600);
```

Each returns a `Rendered` output you can save, store, download, return from a controller, stream or turn into a data
URI. See [Output](output.md).

## In Blade

```blade
<x-qr :content="$event->url" class="size-40" />
<x-qr::code :content="$event->url" as="img" :size="600" />
```

See [Blade](blade.md).

## Styles you reuse

Name a style once, in a service provider or in `config/qr.php`:

```php
Qr::styles()->register('brand', fn () => Qr::style()
    ->modules('dots-medium')
    ->eyes('rounded')
    ->paint('#1e1b4b'));
```

and use it by name anywhere:

```php
Qr::url($url)->style('brand')->svg();
```

```blade
<x-qr :content="$url" style="brand" />
```

See [Styling](styling.md).
