<?php

declare(strict_types=1);

namespace ExeQue\Qr\Payloads;

use ExeQue\Qr\Exceptions\InvalidPayloadException;
use ExeQue\Qr\Payloads\Concerns\WritesContentLines;
use ExeQue\Qr\Payloads\Contracts\Payload;
use Illuminate\Support\Traits\Conditionable;

/**
 * A contact card: vCard 3.0, which every phone reads.
 */
final readonly class Vcard implements Payload
{
    use Conditionable;
    use WritesContentLines;

    /**
     * @param list<array{string, string|null}> $phones Numbers and their types.
     * @param list<string> $emails
     * @param list<array{string, string, string, string, string, string|null}> $addresses
     */
    public function __construct(
        public ?string $name = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $organization = null,
        public ?string $title = null,
        public array $phones = [],
        public array $emails = [],
        public ?string $url = null,
        public array $addresses = [],
        public ?string $note = null,
    ) {
    }

    /**
     * The full name; first and last names are taken from it unless given.
     */
    public function name(string $name, ?string $first = null, ?string $last = null): self
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [$name];

        return clone($this, [
            'name' => $name,
            'firstName' => $first ?? (count($parts) > 1 ? implode(' ', array_slice($parts, 0, -1)) : $name),
            'lastName' => $last ?? (count($parts) > 1 ? $parts[array_key_last($parts)] : ''),
        ]);
    }

    /**
     * @param string|null $type Such as cell, work or home.
     */
    public function phone(string $number, ?string $type = null): self
    {
        return clone($this, ['phones' => [...$this->phones, [$number, $type]]]);
    }

    public function email(string $address): self
    {
        return clone($this, ['emails' => [...$this->emails, $address]]);
    }

    public function organization(string $organization): self
    {
        return clone($this, ['organization' => $organization]);
    }

    public function title(string $title): self
    {
        return clone($this, ['title' => $title]);
    }

    public function url(string $url): self
    {
        return clone($this, ['url' => $url]);
    }

    /**
     * @param string|null $type Such as work or home.
     */
    public function address(string $street = '', string $city = '', string $region = '', string $postalCode = '', string $country = '', ?string $type = null): self
    {
        return clone($this, ['addresses' => [...$this->addresses, [$street, $city, $region, $postalCode, $country, $type]]]);
    }

    public function note(string $note): self
    {
        return clone($this, ['note' => $note]);
    }

    public function toString(): string
    {
        if ($this->name === null) {
            throw InvalidPayloadException::missing('vCard', 'name');
        }

        $text = self::escapeText(...);

        return self::contentLines(array_values(array_filter([
            'BEGIN:VCARD',
            'VERSION:3.0',
            'N:'.$text($this->lastName ?? '').';'.$text($this->firstName ?? '').';;;',
            'FN:'.$text($this->name),
            $this->organization === null ? null : 'ORG:'.$text($this->organization),
            $this->title === null ? null : 'TITLE:'.$text($this->title),
            ...array_map(fn (array $phone): string => 'TEL'.self::type($phone[1]).':'.$text($phone[0]), $this->phones),
            ...array_map(fn (string $email): string => 'EMAIL:'.$text($email), $this->emails),
            $this->url === null ? null : 'URL:'.$text($this->url),
            ...array_map(fn (array $address): string => 'ADR'.self::type($address[5]).':;;'
                .implode(';', array_map($text, array_slice($address, 0, 5))), $this->addresses),
            $this->note === null ? null : 'NOTE:'.$text($this->note),
            'END:VCARD',
        ])));
    }

    /**
     * A TYPE parameter: a plain word such as "work" or "cell", as the parameter allows nothing else unquoted.
     */
    private static function type(?string $type): string
    {
        if ($type === null) {
            return '';
        }

        return preg_match('/^[A-Za-z0-9-]+$/', $type) === 1 ? ';TYPE='.strtoupper($type) : throw InvalidPayloadException::invalid('vCard', 'type', $type);
    }
}
