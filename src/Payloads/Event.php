<?php

declare(strict_types=1);

namespace ExeQue\Qr\Payloads;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use ExeQue\Qr\Exceptions\InvalidPayloadException;
use ExeQue\Qr\Payloads\Concerns\WritesContentLines;
use ExeQue\Qr\Payloads\Contracts\Payload;

/**
 * A calendar event: an iCalendar VEVENT (RFC 5545), times in UTC.
 */
final readonly class Event implements Payload
{
    use WritesContentLines;

    public function __construct(
        public ?string $title = null,
        public ?DateTimeInterface $starts = null,
        public ?DateTimeInterface $ends = null,
        public ?string $location = null,
        public ?string $description = null,
        public bool $allDay = false,
    ) {
    }

    public function title(string $title): self
    {
        return clone($this, ['title' => $title]);
    }

    public function starts(DateTimeInterface $starts): self
    {
        return clone($this, ['starts' => $starts]);
    }

    public function ends(DateTimeInterface $ends): self
    {
        return clone($this, ['ends' => $ends]);
    }

    public function location(string $location): self
    {
        return clone($this, ['location' => $location]);
    }

    public function description(string $description): self
    {
        return clone($this, ['description' => $description]);
    }

    /**
     * Whole days, without times.
     */
    public function allDay(bool $allDay = true): self
    {
        return clone($this, ['allDay' => $allDay]);
    }

    public function toString(): string
    {
        if ($this->title === null || $this->starts === null) {
            throw InvalidPayloadException::missing('event', $this->title === null ? 'title' : 'start');
        }

        $text = self::escapeText(...);

        return self::contentLines(array_values(array_filter([
            'BEGIN:VEVENT',
            'SUMMARY:'.$text($this->title),
            'DTSTART'.$this->time($this->starts),
            $this->ends === null ? null : 'DTEND'.$this->time($this->ends),
            $this->location === null ? null : 'LOCATION:'.$text($this->location),
            $this->description === null ? null : 'DESCRIPTION:'.$text($this->description),
            'END:VEVENT',
        ])));
    }

    /**
     * `:20261024T150000Z`, or for a whole day `;VALUE=DATE:20261024`.
     */
    private function time(DateTimeInterface $time): string
    {
        return $this->allDay
            ? ';VALUE=DATE:'.$time->format('Ymd')
            : ':'.DateTimeImmutable::createFromInterface($time)->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
    }
}
