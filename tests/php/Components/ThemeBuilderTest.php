<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Components;

use Celerik\Ui\Components\Grid;
use Celerik\Ui\Components\ThemeBuilder;
use Celerik\Ui\Support\Html;
use Celerik\Ui\Theme\Contrast;
use Celerik\Ui\Theme\Theme;
use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ThemeBuilderTest extends TestCase
{
    private function dom(string $html): DOMXPath
    {
        $document = new DOMDocument();
        libxml_use_internal_errors(true); // HTML5 elements (output, datalist, section)
        $document->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>');
        libxml_clear_errors();

        return new DOMXPath($document);
    }

    public function testRendersAFormWithEveryTokenOfBothModes(): void
    {
        $html = ThemeBuilder::make('builder')->theme(Theme::preset('material'))->render();
        $xpath = $this->dom($html);

        self::assertStringStartsWith('<div id="builder" class="celerik-theme-builder" data-celerik="themebuilder"', $html);
        self::assertSame('builder-title', $xpath->query('//form')->item(0)?->getAttribute('aria-labelledby'));
        foreach (['light', 'dark'] as $mode) {
            foreach (Theme::TOKENS as $token) {
                $input = $xpath->query(sprintf('//input[@name="theme[%s][%s]"]', $mode, $token))->item(0);
                self::assertInstanceOf(DOMElement::class, $input, "$mode.$token");
                self::assertSame(Theme::preset('material')->palette($mode)[$token], $input->getAttribute('value'));
            }
        }
        self::assertSame(2, $xpath->query('//fieldset[@data-celerik-mode]/legend')->length);
        self::assertSame('material', $xpath->query('//select[@name="theme[base]"]/option[@selected]')->item(0)?->getAttribute('value'));
        self::assertSame('elevated', $xpath->query('//select[@name="theme[shadow]"]/option[@selected]')->item(0)?->getAttribute('value'));
        self::assertSame('0.75', $xpath->query('//input[@name="theme[radius]"]')->item(0)?->getAttribute('value'));
    }

    public function testEveryControlIsLabelled(): void
    {
        $xpath = $this->dom(ThemeBuilder::make('builder')->saveUrl('/theme')->csrf('token')->render());

        $unlabelled = [];
        foreach ($xpath->query('//input[not(@type="hidden")] | //select') as $control) {
            assert($control instanceof DOMElement);
            $id = $control->getAttribute('id');
            $named = $control->getAttribute('aria-label') !== ''
                || ($id !== '' && $xpath->query(sprintf('//label[@for="%s"]', $id))->length === 1);
            if (!$named) {
                $unlabelled[] = $id ?: $control->getAttribute('name');
            }
        }

        self::assertSame([], $unlabelled);
        self::assertSame('Primary, color picker', $xpath->query('//input[@id="builder-light-primary-picker"]')->item(0)?->getAttribute('aria-label'));
        self::assertSame('builder-light-primary-help', $xpath->query('//input[@id="builder-light-primary"]')->item(0)?->getAttribute('aria-describedby'));
    }

    public function testTheContrastReportIsComputedInPhp(): void
    {
        $theme = Theme::preset('bootstrap')->color('light', 'danger', '#dd3545')->color('dark', 'inputBorder', '#495057');
        $xpath = $this->dom(ThemeBuilder::make('b')->theme($theme)->render());

        self::assertSame(2 * count(Contrast::pairs()), $xpath->query('//li[contains(@class, "celerik-theme-builder__pair")]')->length);
        self::assertSame('2 pairs are below their minimum.', trim((string) $xpath->query('//p[contains(@class, "__summary")]')->item(0)?->textContent));
        $failed = $xpath->query('//li[contains(@class, "is-fail")]');
        self::assertSame(2, $failed->length);
        // Text, not only color, says it failed (WCAG 1.4.1); ratios are truncated, never rounded up.
        self::assertSame('Fail Light · Error messages: 4.49:1 (needs 4.5:1)', trim((string) $failed->item(0)?->textContent));
        self::assertSame('light.danger.bodyBg', $failed->item(0)?->getAttribute('data-celerik-pair'));

        $one = $this->dom(ThemeBuilder::make('c')->theme(Theme::preset('bootstrap')->color('light', 'danger', '#dd3545'))->render());
        self::assertSame('1 pair is below its minimum.', trim((string) $one->query('//p[contains(@class, "__summary")]')->item(0)?->textContent));
        $none = $this->dom(ThemeBuilder::make('d')->render());
        self::assertSame('Every pair meets its minimum.', trim((string) $none->query('//p[contains(@class, "__summary")]')->item(0)?->textContent));
    }

    public function testSavingNeedsASaveUrlAndCarriesTheCsrfToken(): void
    {
        $plain = ThemeBuilder::make('b')->render();
        self::assertStringNotContainsString('type="submit"', $plain);

        $xpath = $this->dom(ThemeBuilder::make('b')->saveUrl('/theme')->csrf('t<k>', 'csrf_token')->render());
        self::assertSame('/theme', $xpath->query('//form')->item(0)?->getAttribute('action'));
        self::assertSame('t<k>', $xpath->query('//input[@name="csrf_token"]')->item(0)?->getAttribute('value'));
        self::assertSame(1, $xpath->query('//button[@type="submit"]')->length);
    }

    public function testJavaScriptOnlyControlsStartHidden(): void
    {
        $xpath = $this->dom(ThemeBuilder::make('b')->render());

        foreach (['__preset', '__history', '__export', '__modes'] as $part) {
            $element = $xpath->query(sprintf('//*[contains(@class, "celerik-theme-builder%s")]', $part))->item(0);
            self::assertInstanceOf(DOMElement::class, $element, $part);
            self::assertTrue($element->hasAttribute('hidden'), $part);
        }
    }

    public function testThePreviewHoldsComponentsAndEscapesStrings(): void
    {
        $grid = Grid::make('users')->caption('Users')->column('name')->rows([['name' => 'Ada']]);
        $html = ThemeBuilder::make('b')->preview($grid, '<script>alert(1)</script>', Html::raw('<p class="lead">Trusted</p>'))->render();

        self::assertStringContainsString('<div class="celerik-theme-builder__canvas" data-bs-theme="light">', $html);
        self::assertStringContainsString('<table id="users"', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringContainsString('<p class="lead">Trusted</p>', $html);
    }

    public function testOptionsCarryTheThemePresetsPairsAndLabels(): void
    {
        $options = ThemeBuilder::make('b')
            ->theme(Theme::preset('tailwind')->name('Acme'))
            ->labels(['save' => 'Enregistrer', 'primary' => 'Principale'])
            ->assetsUrl('/vendor/celerik/')
            ->toArray();

        self::assertSame('Acme', $options['theme']['name']);
        self::assertSame(Theme::BASES, array_keys($options['presets']));
        self::assertSame(Contrast::pairs(), $options['pairs']);
        self::assertSame('Enregistrer', $options['labels']['save']);
        self::assertArrayNotHasKey('primary', $options['labels'], 'Token labels are rendered by PHP.');
        self::assertSame('/vendor/celerik', $options['assetsUrl']);
        self::assertArrayNotHasKey('saveUrl', $options);
        self::assertStringContainsString('>Principale</label>', ThemeBuilder::make('b')->labels(['primary' => 'Principale'])->render());
    }

    public function testRejectsUnknownLabelsAndCsrfFields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ThemeBuilder::make('b')->labels(['submit' => 'Go']);
    }

    public function testRejectsAnInvalidCsrfField(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ThemeBuilder::make('b')->csrf('t', 'a"b');
    }
}
