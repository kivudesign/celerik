<?php

declare(strict_types=1);

namespace Celerik\Ui\Theme;

use InvalidArgumentException;

/**
 * WCAG 2.x contrast checks for theme palettes. The pairs come from theme/contrast.json,
 * which the ThemeBuilder and the tests use too.
 */
final class Contrast
{
    /** @var list<array{foreground: string, background: string, minimum: float, label: string, wcag: string}>|null */
    private static ?array $pairs = null;

    /** Contrast ratio of two "#rrggbb" colors, from 1 to 21. */
    public static function ratio(string $foreground, string $background): float
    {
        $lighter = max(self::luminance($foreground), self::luminance($background));
        $darker = min(self::luminance($foreground), self::luminance($background));

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Every pair, in light then dark mode.
     *
     * @return list<ContrastResult>
     */
    public static function report(Theme $theme): array
    {
        $results = [];
        foreach (Theme::MODES as $mode) {
            $palette = $theme->palette($mode);
            foreach (self::pairs() as $pair) {
                $results[] = new ContrastResult(
                    $mode,
                    $pair['foreground'],
                    $pair['background'],
                    $palette[$pair['foreground']],
                    $palette[$pair['background']],
                    self::ratio($palette[$pair['foreground']], $palette[$pair['background']]),
                    $pair['minimum'],
                    $pair['label'],
                    $pair['wcag'],
                );
            }
        }

        return $results;
    }

    /** @return list<ContrastResult> Only the pairs below their minimum. */
    public static function failures(Theme $theme): array
    {
        $failed = static fn (ContrastResult $result): bool => !$result->passes();

        return array_values(array_filter(self::report($theme), $failed));
    }

    /** @return list<array{foreground: string, background: string, minimum: float, label: string, wcag: string}> */
    public static function pairs(): array
    {
        if (self::$pairs === null) {
            $json = (string) file_get_contents(dirname(__DIR__, 2) . '/theme/contrast.json');
            $data = json_decode($json, true, 8, JSON_THROW_ON_ERROR);
            self::$pairs = array_map(static fn (array $pair): array => [
                'foreground' => (string) $pair['foreground'],
                'background' => (string) $pair['background'],
                'minimum' => (float) $pair['minimum'],
                'label' => (string) $pair['label'],
                'wcag' => (string) $pair['wcag'],
            ], $data['pairs']);
        }

        return self::$pairs;
    }

    private static function luminance(string $color): float
    {
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid color "%s": use #rrggbb.', $color));
        }
        [$r, $g, $b] = array_map(static function (string $hex): float {
            $channel = hexdec($hex) / 255;

            return $channel <= 0.03928 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
        }, str_split(substr($color, 1), 2));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}
