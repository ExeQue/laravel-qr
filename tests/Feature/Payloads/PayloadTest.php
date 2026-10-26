<?php

declare(strict_types=1);

namespace Tests\Feature\Payloads;

use BadMethodCallException;
use Closure;
use DateTimeImmutable;
use DateTimeZone;
use ExeQue\Qr\Builders\PendingQr;
use ExeQue\Qr\Exceptions\InvalidPayloadException;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Payloads\Concerns\WritesContentLines;
use ExeQue\Qr\Payloads\Contracts\Payload;
use ExeQue\Qr\Payloads\Email;
use ExeQue\Qr\Payloads\Enums\WifiSecurity;
use ExeQue\Qr\Payloads\Event;
use ExeQue\Qr\Payloads\Geo;
use ExeQue\Qr\Payloads\Sms;
use ExeQue\Qr\Payloads\Text;
use ExeQue\Qr\Payloads\Url;
use ExeQue\Qr\Payloads\Vcard;
use ExeQue\Qr\Payloads\Wifi;
use ExeQue\Qr\Registries\PayloadRegistry;
use stdClass;

covers(Url::class, Wifi::class, Vcard::class, Email::class, Sms::class, Geo::class, Event::class, Text::class, WritesContentLines::class, WifiSecurity::class, InvalidPayloadException::class, PayloadRegistry::class, PendingQr::class, \ExeQue\Qr\Qr::class);

pest()->group('payloads');

final readonly class MobilePay implements Payload
{
    public function __construct(
        private string $phone,
        private int $amount = 0,
    ) {
    }

    public function toString(): string
    {
        return "mobilepay://send?phone={$this->phone}&amount={$this->amount}";
    }
}

dataset('payloads', [
    'url' => [fn (): PendingQr => Qr::url('https://example.com/e/ABCD1234?ref=card#rsvp')->utm(source: 'table-card', campaign: 'wedding')],
    'wifi' => [fn (): PendingQr => Qr::wifi()->ssid('Wedding; "Guests"')->password('cake:2026,yes\\')->wpa()->hidden()],
    'wifi, open' => [fn (): PendingQr => Qr::wifi()->ssid('Café')->open()],
    'wifi, wep' => [fn (): PendingQr => Qr::wifi()->ssid('Old')->password('secret')->wep()],
    'vcard' => [fn (): PendingQr => Qr::vcard()
        ->name('Alex Morgan')
        ->organization('Acme, Inc.')
        ->title('Host')
        ->phone('+4512345678', 'cell')
        ->phone('+4587654321')
        ->email('alex@example.com')
        ->url('https://example.com')
        ->address('Main Street 1', 'Copenhagen', '', '1000', 'Denmark', 'home')
        ->note("Line one\nLine two; with, punctuation and a backslash \\ that runs on long enough to need folding æøå")],
    'email' => [fn (): PendingQr => Qr::email('host@example.com')->subject('Hello & welcome')->body("Yes please\nSee you")->cc('a@example.com')->bcc('b@example.com', 'c@example.com')],
    'sms' => [fn (): PendingQr => Qr::sms('+4512345678')->message('Yes please: two of us')],
    'geo' => [fn (): PendingQr => Qr::geo(55.6761, 12.5683)->altitude(14.5)],
    'event' => [fn (): PendingQr => Qr::event()
        ->title('Our wedding; finally')
        ->starts(new DateTimeImmutable('2026-10-24 15:00', new DateTimeZone('Europe/Copenhagen')))
        ->ends(new DateTimeImmutable('2026-10-24 23:30', new DateTimeZone('Europe/Copenhagen')))
        ->location('The chapel, Main Street 1')
        ->description('Dinner after')],
    'event, all day' => [fn (): PendingQr => Qr::event()->title('Holiday')->starts(new DateTimeImmutable('2026-12-24'))->ends(new DateTimeImmutable('2026-12-27'))->allDay()],
    'text' => [fn (): PendingQr => Qr::text('anything at all')],
]);

describe('formats', function (): void {
    it('scans as what it encodes', function (PendingQr $code): void {
        expect($code->png(600))->toDecodeAs($code->text());
    })->with('payloads')->group('scan');

    it('leaves a URL alone without parameters, and adds to an existing query', function (): void {
        expect(Qr::url('https://example.com')->text())->toBe('https://example.com')
            ->and(Qr::url('https://example.com/?a=1')->query(['b' => 'two words'])->text())->toBe('https://example.com/?a=1&b=two%20words')
            ->and(Qr::email('a@example.com')->text())->toBe('mailto:a@example.com');
    });

    it('takes the usual details straight away', function (): void {
        $start = new DateTimeImmutable('2026-10-24 15:00 UTC');

        expect(Qr::wifi('Wedding', 'cake2026')->text())->toBe('WIFI:T:WPA;S:Wedding;P:cake2026;;')
            ->and(Qr::wifi('Café', 'ignored', 'nopass')->text())->toBe('WIFI:T:nopass;S:Café;;')
            ->and(Qr::wifi('Hidden', 'x', WifiSecurity::Wep, hidden: true)->text())->toBe('WIFI:T:WEP;S:Hidden;P:x;H:true;;')
            ->and(Qr::sms('+4512345678', 'Yes please')->text())->toBe('SMSTO:+4512345678:Yes please')
            ->and(Qr::email('host@example.com', subject: 'Hello')->text())->toBe('mailto:host@example.com?subject=Hello')
            ->and(Qr::vcard('Alex Morgan', phone: '+4512345678', email: 'alex@example.com', organization: 'Acme')->text())
            ->toContain('FN:Alex Morgan')->toContain('TEL:+4512345678')->toContain('EMAIL:alex@example.com')->toContain('ORG:Acme')
            ->and(Qr::event('Party', $start, location: 'Home')->text())->toContain('SUMMARY:Party')->toContain('DTSTART:20261024T150000Z')->toContain('LOCATION:Home');
    });

    it('encodes each kind of content to its format', function (PendingQr $code): void {
        expect($code->text())->toMatchSnapshot();
    })->with('payloads');
});

describe('escaping', function (): void {
    it('escapes Wi-Fi fields', function (): void {
        expect(Qr::wifi()->ssid('a;b,c:d"e\\f')->password('p')->text())->toBe('WIFI:T:WPA;S:a\;b\,c\:d\"e\\\\f;P:p;;');
    });

    it('escapes and folds vCard and iCalendar text', function (): void {
        // One byte before the two-byte characters puts the fold in the middle of one.
        $card = Qr::vcard()->name('Ann Lee')->note('x'.str_repeat('æ', 60))->text();
        $lines = explode("\r\n", rtrim($card));

        foreach ($lines as $line) {
            expect(strlen($line))->toBeLessThanOrEqual(75)
                ->and(mb_check_encoding($line, 'UTF-8'))->toBeTrue();
        }

        expect($card)->toContain("\r\n ")
            ->and(Qr::vcard()->name('A;B,C\\D')->text())->toContain('FN:A\;B\,C\\\\D')
            ->and(Qr::vcard()->name('Prince')->text())->toContain('N:;Prince;;;')
            ->and(Qr::vcard()->name('Mary Ann Lee', last: 'Lee-Smith')->text())->toContain('N:Lee-Smith;Mary Ann;;;');
    });

    it('folds lines of text that is not UTF-8', function (): void {
        $card = new \ExeQue\Qr\Payloads\Vcard(str_repeat("\xA9", 200))->toString();

        expect(max(array_map(strlen(...), explode("\r\n", $card))))->toBeLessThanOrEqual(75);
    });

    it('keeps vCard values on their own line', function (): void {
        $card = new \ExeQue\Qr\Payloads\Vcard('Ada')->email("a@b.c\r\nTEL:666")->phone("1\nX:2")->url("https://x.y/;a")->toString();

        expect($card)->toContain("EMAIL:a@b.c\\nTEL:666\r\n")
            ->toContain("TEL:1\\nX:2\r\n")
            ->toContain("URL:https://x.y/\;a\r\n")
            ->and(fn () => new \ExeQue\Qr\Payloads\Vcard('Ada')->phone('1', 'cell:x')->toString())
            ->toThrow(\ExeQue\Qr\Exceptions\InvalidPayloadException::class, 'The vCard type "cell:x" is not valid.');
    });
});

describe('on the builder', function (): void {
    it('builds content on the code, styling and all', function (): void {
        $code = Qr::wifi()->ssid('Wedding')->margin(2)->password('cake2026');

        expect($code->text())->toBe('WIFI:T:WPA;S:Wedding;P:cake2026;;')
            ->and($code->toStyle()->margin)->toBe(2)
            ->and($code->content)->toBeInstanceOf(Wifi::class);
    });

    it('passes on what a content method returns that is not content', function (): void {
        $payload = new class () implements Payload {
            public function toString(): string
            {
                return 'x';
            }

            public function length(): int
            {
                return 1;
            }
        };

        expect(Qr::make($payload)->length())->toBe(1);
    });

    it('refuses methods neither it nor its content has', function (): void {
        Qr::text('x')->sparkle();
    })->throws(BadMethodCallException::class, 'Method '.PendingQr::class.'::sparkle does not exist.');

    it('refuses methods on plain text content', function (): void {
        Qr::make('x')->ssid('y');
    })->throws(BadMethodCallException::class);
});

describe('registry', function (): void {
    it('makes registered content by name, the application\'s own too', function (): void {
        Qr::payloads()->register('mobilepay', MobilePay::class);

        expect(Qr::payload('mobilepay', phone: '12345678', amount: 100)->text())->toBe('mobilepay://send?phone=12345678&amount=100')
            ->and(Qr::payload('sms', number: '+45')->text())->toBe('SMSTO:+45:')
            ->and(Qr::payloads()->names())->toContain('url', 'wifi', 'mobilepay');
    });

    it('takes kinds of content from the config, and refuses what is not content', function (): void {
        config()->set('qr.payloads', ['mobilepay' => MobilePay::class]);

        expect(Qr::payloads()->has('mobilepay'))->toBeTrue()
            ->and(fn () => Qr::payloads()->register('odd', new stdClass()) ?? Qr::payloads()->get('odd'))
            ->toThrow(\ExeQue\Qr\Exceptions\RegistryException::class, 'The payload "odd" is neither');
    });
});

describe('validation', function (): void {
    it('refuses content it cannot encode', function (callable $build, string $message): void {
        expect($build)->toThrow(InvalidPayloadException::class, $message);
    })->with([
        [fn () => Qr::url('not a url'), '"not a url" is not a URL.'],
        [fn () => Qr::wifi()->text(), 'A Wi-Fi needs a ssid.'],
        [fn () => Qr::vcard()->text(), 'A vCard needs a name.'],
        [fn () => Qr::event()->starts(new DateTimeImmutable())->text(), 'A event needs a title.'],
        [fn () => Qr::event()->title('x')->text(), 'A event needs a start.'],
        [fn () => Qr::geo(91, 0), 'A latitude is -90 to 90 and a longitude -180 to 180; 91, 0 given.'],
    ]);

    it('takes only addresses for email', function (Closure $make): void {
        expect($make)->toThrow(\ExeQue\Qr\Exceptions\InvalidPayloadException::class, 'is not valid');
    })->with([
        'a query in the address' => [fn () => new \ExeQue\Qr\Payloads\Email('victim@x.com?bcc=evil@x.com')],
        'a bad cc' => [fn () => new \ExeQue\Qr\Payloads\Email('a@x.com')->cc('nope')],
        'a bad bcc' => [fn () => new \ExeQue\Qr\Payloads\Email('a@x.com')->bcc('a@x.com', 'b?c')],
    ]);

    it('refuses links that run or read something on the device', function (string $url): void {
        new \ExeQue\Qr\Payloads\Url($url);
    })->with(['javascript://x/%0Aalert(1)', 'JavaScript://x/', 'data://text/plain,x', 'file:///etc/passwd', 'vbscript://x'])
        ->throws(\ExeQue\Qr\Exceptions\InvalidPayloadException::class);

});
