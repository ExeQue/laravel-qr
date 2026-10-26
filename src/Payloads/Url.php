<?php

declare(strict_types=1);

namespace ExeQue\Qr\Payloads;

use ExeQue\Qr\Exceptions\InvalidPayloadException;
use ExeQue\Qr\Payloads\Contracts\Payload;

/**
 * A web address, with query parameters added to whatever it already has.
 */
final readonly class Url implements Payload
{
    /**
     * @param array<string, string> $query Added to the address's own query.
     */
    public function __construct(
        public string $url,
        public array $query = [],
    ) {
        // Schemes that run or read something on the scanning device are no links to share.
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (filter_var($url, FILTER_VALIDATE_URL) === false || in_array($scheme, ['javascript', 'vbscript', 'data', 'file'], true)) {
            throw InvalidPayloadException::url($url);
        }
    }

    /**
     * @param array<string, string> $parameters
     */
    public function query(array $parameters): self
    {
        return clone($this, ['query' => [...$this->query, ...$parameters]]);
    }

    /**
     * Campaign tracking parameters; those not given are left out.
     */
    public function utm(?string $source = null, ?string $medium = null, ?string $campaign = null, ?string $term = null, ?string $content = null): self
    {
        return $this->query(array_filter([
            'utm_source' => $source,
            'utm_medium' => $medium,
            'utm_campaign' => $campaign,
            'utm_term' => $term,
            'utm_content' => $content,
        ], fn (?string $value): bool => $value !== null));
    }

    public function toString(): string
    {
        if ($this->query === []) {
            return $this->url;
        }

        [$address, $fragment] = array_pad(explode('#', $this->url, 2), 2, null);
        $separator = str_contains($address, '?') ? '&' : '?';

        return $address.$separator.http_build_query($this->query, encoding_type: PHP_QUERY_RFC3986).($fragment === null ? '' : "#{$fragment}");
    }
}
