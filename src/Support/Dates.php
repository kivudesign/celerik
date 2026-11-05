<?php

declare(strict_types=1);

namespace Celerik\Ui\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException;

/**
 * Strict ISO 8601 date parsing for values coming from requests and databases.
 *
 * Only `2026-11-05`, `2026-11-05T14:30`, `2026-11-05 14:30:00` and the same with seconds,
 * fractions and a `Z` / `+01:00` offset are accepted: PHP's relative formats ("next monday")
 * never are.
 */
final class Dates
{
    private const ISO_PATTERN = '/^\d{4}-\d{2}-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d{1,6})?)?(Z|[+-]\d{2}:?\d{2})?)?$/';

    public static function parse(mixed $value, ?DateTimeZone $timeZone = null): DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        if (!is_string($value) || preg_match(self::ISO_PATTERN, $value) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid date "%s": expected ISO 8601.', Arr::toText($value)));
        }

        try {
            $date = new DateTimeImmutable($value, $timeZone);
        } catch (Exception) {
            throw new InvalidArgumentException(sprintf('Invalid date "%s".', $value));
        }

        // Reject overflowing values such as 2026-02-31, which PHP silently rolls over.
        if ($date->format('Y-m-d') !== substr($value, 0, 10)) {
            throw new InvalidArgumentException(sprintf('Invalid date "%s".', $value));
        }

        return $date;
    }

    public static function isDateOnly(mixed $value): bool
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }
}
