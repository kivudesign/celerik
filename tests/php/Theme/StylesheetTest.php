<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Theme;

use Celerik\Ui\Theme\Stylesheet;
use Celerik\Ui\Theme\Theme;
use PHPUnit\Framework\TestCase;

final class StylesheetTest extends TestCase
{
    public function testWritesBothPalettesWithBootstrapsDerivedProperties(): void
    {
        $css = Theme::preset('material')->color('light', 'primary', '#0b5394')->toCss();

        self::assertStringStartsWith(
            "/* Celerik UI theme \"Material\" (base: material). Load it after celerik.css and celerik-material.css. */\n"
            . ":root,\n[data-bs-theme=light] {\n  --bs-primary: #0b5394;\n  --bs-primary-rgb: 11, 83, 148;\n",
            $css,
        );
        self::assertStringContainsString("[data-bs-theme=dark] {\n  --bs-primary: #d0bcff;\n", $css);
        self::assertStringContainsString('  --bs-link-color: #0b5394;', $css);
        self::assertStringContainsString('  --bs-form-invalid-color: #b3261e;', $css);
        self::assertStringContainsString('  --celerik-input-border: #79747e;', $css);
        self::assertStringContainsString("  --celerik-radius: 0.75rem;\n", $css);
        self::assertStringContainsString('  --celerik-surface-shadow: ' . Stylesheet::SHADOWS['elevated'] . ';', $css);
        self::assertStringNotContainsString('.btn-primary', $css, 'The material stylesheet binds its components itself.');
        self::assertStringNotContainsString('--bs-body-font-family', $css);
    }

    public function testTheBootstrapBaseBindsComponentsCompiledWithBootstrapsBlue(): void
    {
        $css = Theme::preset('bootstrap')->toCss();

        self::assertStringContainsString('Load it after celerik.css. */', $css);
        self::assertStringContainsString(".btn-primary {\n  --bs-btn-color: var(--celerik-on-primary);", $css);
        self::assertStringContainsString('.pagination {', $css);
        self::assertStringContainsString(".form-check-input:checked {\n  background-color: var(--celerik-primary);", $css);
        // FullCalendar's white text would sit on a light primary in dark mode.
        self::assertStringContainsString("  --fc-button-text-color: var(--celerik-on-primary);\n", $css);
        self::assertStringContainsString("  --fc-event-text-color: var(--celerik-on-primary);\n", $css);
        self::assertStringEndsWith("}\n", $css);
    }

    public function testTheTailwindBaseDerivesItsFocusRingAndHeaderColor(): void
    {
        $css = Theme::preset('tailwind')->toCss();

        self::assertStringContainsString('  --celerik-focus-ring: 0 0 0 2px #ffffff, 0 0 0 4px #4f46e5;', $css);
        self::assertStringContainsString('  --celerik-focus-ring: 0 0 0 2px #0f172a, 0 0 0 4px #818cf8;', $css);
        self::assertStringContainsString('  --celerik-grid-header-color: #475569;', $css);
        self::assertStringContainsString(
            "  --bs-body-font-family: ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji';",
            $css,
        );
    }

    public function testShapeTokensAreOptionalAndNumbersAreWrittenLikeJavaScript(): void
    {
        $theme = Theme::preset('bootstrap')->radius(null)->shadow(null);
        self::assertStringNotContainsString(":root {\n", $theme->toCss());

        self::assertStringContainsString('--celerik-radius: 1rem;', $theme->radius(1.0)->toCss());
        self::assertStringContainsString('--celerik-radius: 0rem;', $theme->radius(0.0)->toCss());
        self::assertStringContainsString('--celerik-radius: 0.125rem;', $theme->radius(0.125)->toCss());
        self::assertStringContainsString('--celerik-radius: 1.5rem;', $theme->radius(1.50)->toCss());
    }

    public function testEveryDeclarationHoldsOnlyValidatedValues(): void
    {
        // Theme rejects anything else (see ThemeTest); here the output's shape is checked.
        foreach (Theme::BASES as $base) {
            $css = Theme::preset($base)->name('Acme 2.0')->toCss();
            $body = substr($css, strpos($css, '*/') + 2);

            self::assertSame(1, substr_count($css, '*/'), 'The name cannot close the comment.');
            self::assertStringNotContainsString('<', $css);
            self::assertStringNotContainsString('@', $body);
            self::assertSame(substr_count($body, '{'), substr_count($body, '}'));
        }
    }
}
