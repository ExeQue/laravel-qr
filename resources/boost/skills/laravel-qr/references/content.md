# Content

A code holds text. `Qr::make()` takes any text as it is; the other starting points write the text for you, in the
format phones read, with every special character escaped.

```php
use ExeQue\Qr\Facades\Qr;

Qr::make('Any text at all');
```

Text beyond ASCII is written as UTF-8 and marked as such, so phones show `blåbærgrød` and `日本語` correctly.

## Links

```php
Qr::url('https://example.com/e/ABCD1234');

Qr::url('https://example.com/menu')
    ->query(['table' => 12])
    ->utm(source: 'poster', medium: 'print', campaign: 'autumn');
// https://example.com/menu?table=12&utm_source=poster&utm_medium=print&utm_campaign=autumn
```

A link must be a valid URL. Links that would run or read something on the scanning device (`javascript:`,
`vbscript:`, `data:` and `file:`) are refused with an `InvalidPayloadException`.

## Wi-Fi

```php
Qr::wifi('Home', 'correct horse battery staple');                 // WPA, the default
Qr::wifi('Cafe', security: 'nopass');                             // an open network
Qr::wifi('Office', 'secret', security: WifiSecurity::Wep, hidden: true);

Qr::wifi()->ssid('Home')->password('secret')->wpa()->hidden();
```

Semicolons, commas, colons, quotes and backslashes in the network name and password are escaped.

## Contact cards

A vCard (version 3.0) for adding a person to the phone's contacts:

```php
Qr::vcard(name: 'Ada Lovelace', phone: '+44 20 7946 0000', email: 'ada@example.com', organization: 'Engines');

Qr::vcard()
    ->name('Ada Lovelace', first: 'Ada', last: 'Lovelace')
    ->phone('+44 20 7946 0000', 'work')
    ->phone('+44 7700 900000', 'cell')
    ->email('ada@example.com')
    ->organization('Analytical Engines')
    ->title('Analyst')
    ->url('https://example.com/ada')
    ->address(street: '1 Main Street', city: 'London', postalCode: 'N1', country: 'UK', type: 'work')
    ->note('Met at the launch');
```

A name is required. Values are escaped and long lines folded as the vCard format requires; a `type` is a plain word
such as `work`, `home` or `cell`.

## Email

```php
Qr::email('hello@example.com', subject: 'Tickets', body: "Hi,\nI would like two.");

Qr::email('hello@example.com')->cc('sales@example.com')->bcc('archive@example.com');
```

Every address must be a valid email address.

## Text messages

```php
Qr::sms('+4512345678', 'On my way');
```

## Places

```php
Qr::geo(55.6761, 12.5683);
Qr::geo(55.6761, 12.5683)->altitude(20);   // meters
```

## Calendar events

```php
Qr::event('Launch', starts: now()->setTime(18, 0), ends: now()->setTime(20, 0), location: 'Copenhagen')
    ->description('Doors open at 17:30');

Qr::event('Holiday', starts: $date)->allDay();
```

Times are written in UTC, so the event lands at the right time whatever the phone's time zone.

## Models and other objects

Anything implementing `ExeQue\Qr\Payloads\Contracts\ProvidesQrContent` can be given to `Qr::make()`:

```php
use ExeQue\Qr\Payloads\Contracts\ProvidesQrContent;
use ExeQue\Qr\Payloads\Url;

class Event extends Model implements ProvidesQrContent
{
    public function qrContent(): string|Payload
    {
        return new Url(route('events.show', $this));
    }
}

Qr::make($event)->svg();
```

## Your own kinds of content

Register a payload class by name, in a service provider or under `payloads` in `config/qr.php`, and start codes
from it with `Qr::payload()`:

```php
Qr::payloads()->register('payment', PaymentLink::class);

Qr::payload('payment', amount: 125, currency: 'DKK');
```

A payload implements `ExeQue\Qr\Payloads\Contracts\Payload`, whose `toString()` gives the text. Methods you call on
the code that the code itself does not have are passed on to its payload, so a payload's own fluent methods chain:

```php
Qr::payload('payment', amount: 125)->currency('EUR')->svg();
```

## Size, error correction and masks

A code grows with its content, from version 1 (21×21 modules) up to version 40 (177×177). More error correction
makes a code survive more damage, at the cost of size:

| Level | Restores about | Use |
|---|---|---|
| `L` | 7% | clean, large prints |
| `M` | 15% | the default |
| `Q` | 25% | styled codes, filters that skip modules |
| `H` | 30% | codes with a logo (set for you, see [Logos](https://exeque.github.io/laravel-qr/docs/logos)) |

```php
use ExeQue\Qr\Encoder\Enums\ErrorCorrection;

Qr::url($url)->errorCorrection(ErrorCorrection::High);
Qr::url($url)->errorCorrection('Q');
Qr::url($url)->minVersion(5);   // at least 37×37, for codes that should match in size
Qr::url($url)->mask(3);         // a fixed mask instead of the best one
```

Content too long for version 40 at the chosen level throws a `ContentTooLongException` that says how many bits it
needs and how many fit.
