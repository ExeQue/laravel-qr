<?php

declare(strict_types=1);

namespace ExeQue\Qr\Payloads;

use ExeQue\Qr\Exceptions\InvalidPayloadException;
use ExeQue\Qr\Payloads\Contracts\Payload;

/**
 * An email to write: a `mailto:` address (RFC 6068) with its subject, body and copies.
 */
final readonly class Email implements Payload
{
    /**
     * @param list<string> $cc
     * @param list<string> $bcc
     */
    public function __construct(
        public string $address,
        public ?string $subject = null,
        public ?string $body = null,
        public array $cc = [],
        public array $bcc = [],
    ) {
        foreach ([$address, ...$cc, ...$bcc] as $email) {
            self::check($email);
        }
    }

    public function subject(string $subject): self
    {
        return clone($this, ['subject' => $subject]);
    }

    public function body(string $body): self
    {
        return clone($this, ['body' => $body]);
    }

    public function cc(string ...$addresses): self
    {
        array_map(self::check(...), $addresses);

        return clone($this, ['cc' => [...$this->cc, ...array_values($addresses)]]);
    }

    public function bcc(string ...$addresses): self
    {
        array_map(self::check(...), $addresses);

        return clone($this, ['bcc' => [...$this->bcc, ...array_values($addresses)]]);
    }

    public function toString(): string
    {
        $query = array_filter([
            'subject' => $this->subject,
            'body' => $this->body === null ? null : str_replace(["\r\n", "\n"], "\r\n", $this->body),
            'cc' => $this->cc === [] ? null : implode(',', $this->cc),
            'bcc' => $this->bcc === [] ? null : implode(',', $this->bcc),
        ], fn (?string $value): bool => $value !== null);

        return 'mailto:'.$this->address.($query === [] ? '' : '?'.http_build_query($query, encoding_type: PHP_QUERY_RFC3986));
    }

    /**
     * An address as mail clients take it, so it cannot carry query parameters of its own.
     */
    private static function check(string $address): void
    {
        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidPayloadException::invalid('email', 'address', $address);
        }
    }
}
