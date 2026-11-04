<?php

declare(strict_types=1);

namespace Celerik\Ui\Support;

/**
 * Dot-notation helpers for row arrays (`user.name` reads `$row['user']['name']`).
 */
final class Arr
{
    /**
     * @param array<array-key, mixed> $row
     */
    public static function get(array $row, string $path): mixed
    {
        if (array_key_exists($path, $row)) {
            return $row[$path];
        }

        $value = $row;

        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $row
     */
    public static function set(array &$row, string $path, mixed $value): void
    {
        $segments = explode('.', $path);
        $last = array_pop($segments);
        $target = &$row;

        foreach ($segments as $segment) {
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }

            $target = &$target[$segment];
        }

        $target[$last] = $value;
    }

    /**
     * Converts a cell value to display text.
     */
    public static function toText(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'Yes' : 'No',
            is_scalar($value) => (string) $value,
            $value instanceof \Stringable => (string) $value,
            $value instanceof \DateTimeInterface => $value->format(DATE_ATOM),
            default => (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        };
    }
}
