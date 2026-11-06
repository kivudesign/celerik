<?php

declare(strict_types=1);

namespace Celerik\Ui\Theme;

/**
 * The contrast of one color pair of a theme, in one mode.
 */
final class ContrastResult
{
    public function __construct(
        public readonly string $mode,
        public readonly string $foreground,
        public readonly string $background,
        public readonly string $foregroundColor,
        public readonly string $backgroundColor,
        public readonly float $ratio,
        public readonly float $minimum,
        public readonly string $label,
        public readonly string $wcag,
    ) {
    }

    public function passes(): bool
    {
        // WCAG thresholds are not rounded: 4.499:1 fails 4.5:1.
        return $this->ratio >= $this->minimum;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s %s: %s on %s = %.2f:1 (needs %s:1, WCAG %s)',
            $this->passes() ? 'PASS' : 'FAIL',
            $this->mode,
            $this->label,
            $this->backgroundColor,
            $this->ratio,
            rtrim(rtrim(number_format($this->minimum, 1), '0'), '.'),
            $this->wcag,
        );
    }
}
