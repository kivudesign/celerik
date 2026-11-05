<?php

declare(strict_types=1);

namespace Celerik\Ui\Data;

use Celerik\Ui\Support\Dates;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use JsonSerializable;

/**
 * A calendar event, validated on construction and serialized in FullCalendar's format.
 *
 * All-day events use an exclusive end date, as FullCalendar does: a one-day event on
 * 2026-11-05 has start 2026-11-05 and no end (or end 2026-11-06).
 */
final class Event implements JsonSerializable
{
    public const MAX_TITLE_LENGTH = 255;

    public readonly string $title;

    /**
     * @param array<string, mixed> $extra Additional data, exposed to JS as `extendedProps`.
     */
    public function __construct(
        string $title,
        public readonly DateTimeImmutable $start,
        public readonly ?DateTimeImmutable $end = null,
        public readonly bool $allDay = false,
        public readonly string|int|null $id = null,
        public readonly ?string $color = null,
        public readonly ?string $url = null,
        public readonly array $extra = [],
    ) {
        $title = trim($title);

        if ($title === '' || mb_strlen($title) > self::MAX_TITLE_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Event title must be 1 to %d characters.',
                self::MAX_TITLE_LENGTH,
            ));
        }

        if ($end !== null && $end < $start) {
            throw new InvalidArgumentException('Event end must not be before its start.');
        }

        // Colors end up in inline styles: accept only hex values and plain color names.
        if ($color !== null && preg_match('/^(#[0-9a-fA-F]{3,8}|[a-zA-Z]{3,20})$/', $color) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid event color "%s".', $color));
        }

        // URLs end up in links: only http(s), root-relative paths and fragments, never javascript:.
        if ($url !== null && preg_match('~^(https?://|/(?!/)|#)~i', $url) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid event URL "%s".', $url));
        }

        $this->title = $title;
    }

    /**
     * Builds an event from request data or a database row.
     *
     * Keys: title, start, end, allDay, id, color, url, extendedProps (or extra).
     * `allDay` defaults to true when `start` is a plain date such as "2026-11-05".
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, ?DateTimeZone $timeZone = null): self
    {
        $title = $data['title'] ?? null;
        if (!is_string($title)) {
            throw new InvalidArgumentException('Event title is required.');
        }

        if (!isset($data['start'])) {
            throw new InvalidArgumentException('Event start is required.');
        }

        $id = $data['id'] ?? null;
        if ($id !== null && !is_int($id) && !(is_string($id) && $id !== '')) {
            throw new InvalidArgumentException('Event id must be an integer or a non-empty string.');
        }

        $extra = $data['extendedProps'] ?? $data['extra'] ?? [];

        return new self(
            title: $title,
            start: Dates::parse($data['start'], $timeZone),
            end: isset($data['end']) && $data['end'] !== '' ? Dates::parse($data['end'], $timeZone) : null,
            allDay: isset($data['allDay'])
                ? filter_var($data['allDay'], FILTER_VALIDATE_BOOL)
                : Dates::isDateOnly($data['start']),
            id: $id,
            color: self::optionalString($data['color'] ?? null),
            url: self::optionalString($data['url'] ?? null),
            extra: is_array($extra) ? $extra : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $format = $this->allDay ? 'Y-m-d' : DATE_ATOM;

        return array_filter([
            'id' => $this->id,
            'title' => $this->title,
            'start' => $this->start->format($format),
            'end' => $this->end?->format($format),
            'allDay' => $this->allDay,
            'color' => $this->color,
            // Readable text on a custom color, whatever the theme's default event text color.
            'textColor' => self::textColorFor($this->color),
            'url' => $this->url,
            'extendedProps' => $this->extra === [] ? null : $this->extra,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Black or white, whichever contrasts more with a hex color (WCAG relative luminance).
     */
    public static function textColorFor(?string $color): ?string
    {
        if ($color === null || preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})([0-9a-f]{2})?$/i', $color, $match) !== 1) {
            return null;
        }

        $hex = strlen($match[1]) === 3 ? preg_replace('/(.)/', '$1$1', $match[1]) : $match[1];
        $channels = array_map(static function (string $pair): float {
            $value = hexdec($pair) / 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, str_split((string) $hex, 2));
        $luminance = 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];

        // Contrast with black is (L + 0.05) / 0.05, with white 1.05 / (L + 0.05): equal at L ≈ 0.179.
        return $luminance > 0.179 ? '#000' : '#fff';
    }

    private static function optionalString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
