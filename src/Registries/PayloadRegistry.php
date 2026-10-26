<?php

declare(strict_types=1);

namespace ExeQue\Qr\Registries;

use ExeQue\Qr\Payloads\Contracts\Payload;
use ExeQue\Qr\Payloads\Email;
use ExeQue\Qr\Payloads\Event;
use ExeQue\Qr\Payloads\Geo;
use ExeQue\Qr\Payloads\Sms;
use ExeQue\Qr\Payloads\Text;
use ExeQue\Qr\Payloads\Url;
use ExeQue\Qr\Payloads\Vcard;
use ExeQue\Qr\Payloads\Wifi;
use ExeQue\Qr\Registries\Concerns\IsRegistry;

/**
 * Kinds of content by name: the built-in ones and an application's own, such as a payment link.
 */
final class PayloadRegistry
{
    /** @use IsRegistry<Payload> */
    use IsRegistry;

    public function kind(): string
    {
        return 'payload';
    }

    protected function contract(): string
    {
        return Payload::class;
    }

    protected function provide(object $value): null
    {
        return null;
    }

    protected function builtIn(): array
    {
        return [
            'url' => Url::class,
            'wifi' => Wifi::class,
            'vcard' => Vcard::class,
            'email' => Email::class,
            'sms' => Sms::class,
            'geo' => Geo::class,
            'event' => Event::class,
            'text' => Text::class,
        ];
    }

    protected function configKey(): string
    {
        return 'payloads';
    }
}
