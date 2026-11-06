<?php

declare(strict_types=1);

namespace Celerik\Ui\Theme;

/**
 * Writes a theme's stylesheet: custom properties for the light and dark palettes and the
 * shape tokens, loaded after celerik.css and the base theme's stylesheet.
 *
 * The output is deterministic: resources/js/theme-css.js produces the same text in the
 * ThemeBuilder, and the tests compare both. Every value comes from a validated Theme, so
 * nothing in it can break out of a declaration.
 */
final class Stylesheet
{
    /** Container shadows: --celerik-surface-shadow. */
    public const SHADOWS = [
        'none' => 'none',
        'subtle' => '0 1px 2px rgba(0, 0, 0, 0.08)',
        'elevated' => '0 1px 2px rgba(0, 0, 0, 0.3), 0 1px 3px 1px rgba(0, 0, 0, 0.15)',
        'outlined' => '0 1px 2px 0 rgba(0, 0, 0, 0.05), 0 0 0 1px var(--bs-border-color)',
    ];

    /** Opacity of --bs-focus-ring-color, as each base stylesheet sets it. */
    private const FOCUS_RING_ALPHA = ['bootstrap' => '0.25', 'material' => '0.35', 'tailwind' => '0.5'];

    // Bootstrap compiles these components with its default blue: bind them to the theme.
    // The material and tailwind stylesheets already bind them to --celerik-primary.
    private const BOOTSTRAP_BINDINGS = <<<'CSS'
        .btn-primary {
          --bs-btn-color: var(--celerik-on-primary);
          --bs-btn-bg: var(--celerik-primary);
          --bs-btn-border-color: var(--celerik-primary);
          --bs-btn-hover-color: var(--celerik-on-primary);
          --bs-btn-hover-bg: var(--celerik-primary-hover);
          --bs-btn-hover-border-color: var(--celerik-primary-hover);
          --bs-btn-active-color: var(--celerik-on-primary);
          --bs-btn-active-bg: var(--celerik-primary-hover);
          --bs-btn-active-border-color: var(--celerik-primary-hover);
          --bs-btn-disabled-color: var(--celerik-on-primary);
          --bs-btn-disabled-bg: var(--celerik-primary);
          --bs-btn-disabled-border-color: var(--celerik-primary);
          --bs-btn-focus-shadow-rgb: var(--bs-primary-rgb);
        }

        .btn-outline-primary {
          --bs-btn-color: var(--celerik-primary);
          --bs-btn-border-color: var(--celerik-primary);
          --bs-btn-hover-color: var(--celerik-on-primary);
          --bs-btn-hover-bg: var(--celerik-primary);
          --bs-btn-hover-border-color: var(--celerik-primary);
          --bs-btn-active-color: var(--celerik-on-primary);
          --bs-btn-active-bg: var(--celerik-primary-hover);
          --bs-btn-active-border-color: var(--celerik-primary-hover);
          --bs-btn-focus-shadow-rgb: var(--bs-primary-rgb);
        }

        .form-control,
        .form-select,
        .form-check-input {
          border-color: var(--celerik-input-border);
        }

        .form-control:focus,
        .form-select:focus,
        .form-check-input:focus {
          border-color: var(--celerik-primary);
          box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
        }

        .form-check-input:checked {
          background-color: var(--celerik-primary);
          border-color: var(--celerik-primary);
        }

        .nav-pills {
          --bs-nav-pills-link-active-bg: var(--celerik-primary);
          --bs-nav-pills-link-active-color: var(--celerik-on-primary);
        }

        .pagination {
          --bs-pagination-active-bg: var(--celerik-primary);
          --bs-pagination-active-border-color: var(--celerik-primary);
          --bs-pagination-active-color: var(--celerik-on-primary);
          --bs-pagination-focus-box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
        }

        .celerik-scheduler {
          --fc-button-text-color: var(--celerik-on-primary);
          --fc-button-bg-color: var(--celerik-primary);
          --fc-button-border-color: var(--celerik-primary);
          --fc-button-hover-bg-color: var(--celerik-primary-hover);
          --fc-button-hover-border-color: var(--celerik-primary-hover);
          --fc-button-active-bg-color: var(--celerik-primary-hover);
          --fc-button-active-border-color: var(--celerik-primary-hover);
          --fc-event-bg-color: var(--celerik-primary);
          --fc-event-border-color: var(--celerik-primary);
          --fc-event-text-color: var(--celerik-on-primary);
        }
        CSS;

    public static function render(Theme $theme): string
    {
        $base = $theme->getBase();
        $files = $base === 'bootstrap' ? 'celerik.css' : sprintf('celerik.css and celerik-%s.css', $base);
        $css = sprintf(
            "/* Celerik UI theme \"%s\" (base: %s). Load it after %s. */\n",
            $theme->getName(),
            $base,
            $files,
        );

        $css .= self::block(":root,\n[data-bs-theme=light]", self::palette($theme, 'light'));
        $css .= "\n" . self::block('[data-bs-theme=dark]', self::palette($theme, 'dark'));

        $shape = [];
        if ($theme->getRadius() !== null) {
            $radius = self::number($theme->getRadius()) . 'rem';
            $shape['--bs-border-radius'] = $radius;
            $shape['--celerik-radius'] = $radius;
        }
        if ($theme->getFontFamily() !== null) {
            $shape['--bs-body-font-family'] = $theme->getFontFamily();
        }
        if ($theme->getShadow() !== null) {
            $shape['--celerik-surface-shadow'] = self::SHADOWS[$theme->getShadow()];
        }
        if ($shape !== []) {
            $css .= "\n" . self::block(':root', $shape);
        }

        if ($base === 'bootstrap') {
            $css .= "\n" . self::BOOTSTRAP_BINDINGS . "\n";
        }

        return $css;
    }

    /** @return array<string, string> */
    private static function palette(Theme $theme, string $mode): array
    {
        $base = $theme->getBase();
        $color = $theme->palette($mode);
        $rgb = static fn (string $token): string => $theme->rgb($mode, $token);

        $properties = [
            '--bs-primary' => $color['primary'],
            '--bs-primary-rgb' => $rgb('primary'),
            '--bs-link-color' => $color['primary'],
            '--bs-link-color-rgb' => $rgb('primary'),
            '--bs-link-hover-color' => $color['primaryHover'],
            '--bs-link-hover-color-rgb' => $rgb('primaryHover'),
            '--bs-primary-text-emphasis' => $color['primaryHover'],
            '--bs-body-bg' => $color['bodyBg'],
            '--bs-body-bg-rgb' => $rgb('bodyBg'),
            '--bs-body-color' => $color['bodyColor'],
            '--bs-body-color-rgb' => $rgb('bodyColor'),
            '--bs-secondary-color' => $color['muted'],
            '--bs-tertiary-bg' => $color['surface'],
            '--bs-tertiary-bg-rgb' => $rgb('surface'),
            '--bs-border-color' => $color['border'],
            '--bs-focus-ring-color' => sprintf('rgba(%s, %s)', $rgb('primary'), self::FOCUS_RING_ALPHA[$base]),
            '--bs-form-invalid-color' => $color['danger'],
            '--bs-form-invalid-border-color' => $color['danger'],
            '--bs-danger-text-emphasis' => $color['danger'],
            '--celerik-primary' => $color['primary'],
            '--celerik-on-primary' => $color['onPrimary'],
            '--celerik-primary-hover' => $color['primaryHover'],
            '--celerik-input-border' => $color['inputBorder'],
        ];

        // The tailwind stylesheet derives these from its palette too.
        if ($base === 'tailwind') {
            $properties['--celerik-grid-header-color'] = $color['muted'];
            $ring = sprintf('0 0 0 2px %s, 0 0 0 4px %s', $color['bodyBg'], $color['primary']);
            $properties['--celerik-focus-ring'] = $ring;
        }

        return $properties;
    }

    /** @param array<string, string> $properties */
    private static function block(string $selector, array $properties): string
    {
        $css = $selector . " {\n";
        foreach ($properties as $name => $value) {
            $css .= sprintf("  %s: %s;\n", $name, $value);
        }

        return $css . "}\n";
    }

    /** 0.5 => "0.5", 1.0 => "1", 0.375 => "0.375" (the same text as JavaScript's String(number)). */
    private static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }
}
