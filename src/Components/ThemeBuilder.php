<?php

declare(strict_types=1);

namespace Celerik\Ui\Components;

use Celerik\Ui\Component;
use Celerik\Ui\Support\Html;
use Celerik\Ui\Theme\Contrast;
use Celerik\Ui\Theme\ContrastResult;
use Celerik\Ui\Theme\Theme;
use InvalidArgumentException;
use Stringable;

/**
 * Edit a theme's tokens with a live preview of real components and a WCAG contrast
 * report, then export the stylesheet (Theme::toCss()) or the theme JSON.
 *
 *     ThemeBuilder::make('builder')
 *         ->theme(Theme::preset('material'))
 *         ->preview($grid, $form, $chart);
 *
 * It is a development tool: put it behind authentication or a local-only route.
 * Without JavaScript it is a form (with a contrast report computed in PHP) that posts the
 * theme to saveUrl(); with JavaScript the preview, the report and the export are live.
 */
final class ThemeBuilder extends Component
{
    /** Texts of the builder; labels() overrides them. */
    public const LABELS = [
        'title' => 'Theme builder',
        'name' => 'Theme name',
        'base' => 'Base look',
        'preset' => 'Start from a preset',
        'loadPreset' => 'Load preset',
        'light' => 'Light mode colors',
        'dark' => 'Dark mode colors',
        'shape' => 'Shape',
        'radius' => 'Corner radius (rem)',
        'font' => 'Font',
        'fontHelp' => 'Family names and generic families, e.g. "Inter, system-ui, sans-serif". '
            . 'Leave it empty to keep the base look\'s font.',
        'shadow' => 'Container shadow',
        'keepBase' => 'Base look default',
        'picker' => 'color picker',
        'report' => 'Contrast (WCAG 2.2 AA)',
        'pass' => 'Pass',
        'fail' => 'Fail',
        'needs' => 'needs',
        'allPass' => 'Every pair meets its minimum.',
        'oneFails' => '1 pair is below its minimum.',
        'manyFail' => '{count} pairs are below their minimum.',
        'preview' => 'Preview',
        'previewMode' => 'Preview mode',
        'modeLight' => 'Light',
        'modeDark' => 'Dark',
        'save' => 'Save theme',
        'export' => 'Export',
        'downloadCss' => 'Download CSS',
        'downloadJson' => 'Download JSON',
        'copyPhp' => 'Copy PHP',
        'copied' => 'Copied.',
        'import' => 'Import JSON',
        'importError' => 'This file is not a valid theme: {error}',
        'imported' => 'Theme imported.',
        'copyFailed' => 'Copying is not allowed here: select the snippet and copy it.',
        'saved' => 'Theme saved.',
        'saveFailed' => 'The theme could not be saved.',
        'undo' => 'Undo',
        'reset' => 'Reset',
        'invalidColor' => 'Use a color like #0d6efd.',
    ];

    /** Names and descriptions of the palette tokens. */
    public const TOKENS = [
        'primary' => ['Primary', 'Buttons, links, active tabs, the current page, calendar events.'],
        'primaryHover' => ['Primary, hovered', 'Hovered and pressed buttons, hovered links.'],
        'onPrimary' => ['Text on primary', 'Text and icons on the primary color.'],
        'bodyBg' => ['Background', 'Page background.'],
        'bodyColor' => ['Text', 'Body text.'],
        'muted' => ['Muted text', 'Help texts, captions, placeholders.'],
        'surface' => ['Surface', 'Grid header, upload area, toolbars.'],
        'border' => ['Border', 'Dividers and decorative borders.'],
        'inputBorder' => ['Field border', 'Borders of form fields (3:1).'],
        'danger' => ['Error', 'Error messages and invalid fields.'],
    ];

    public const BASE_NAMES = ['bootstrap' => 'Bootstrap', 'material' => 'Material', 'tailwind' => 'Tailwind'];

    public const SHADOW_NAMES = [
        'none' => 'None',
        'subtle' => 'Subtle',
        'elevated' => 'Elevated',
        'outlined' => 'Outlined',
    ];

    /** Font stacks suggested in the font field. */
    public const FONTS = [
        'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
        "ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji'",
        'Inter, system-ui, sans-serif',
        'Georgia, "Times New Roman", serif',
        'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace',
    ];

    private ?Theme $theme = null;

    /** @var list<Stringable|string> */
    private array $preview = [];

    private ?string $saveUrl = null;

    private ?string $csrfToken = null;

    private string $csrfField = '_token';

    private ?string $assetsUrl = null;

    /** @var array<string, string> */
    private array $labels = [];

    public static function name(): string
    {
        return 'themebuilder';
    }

    /** The theme to start from (default: the bootstrap preset). */
    public function theme(Theme $theme): self
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Components (or trusted HTML) shown in the preview, e.g. a Grid, a Form and a Chart.
     */
    public function preview(Stringable|string ...$items): self
    {
        array_push($this->preview, ...$items);

        return $this;
    }

    /**
     * Endpoint receiving the theme: a JSON body from the Save button, or the form fields
     * without JavaScript. Read it with ThemeBuilder::fromRequest().
     */
    public function saveUrl(string $url): self
    {
        $this->saveUrl = $url;

        return $this;
    }

    public function csrf(string $token, string $field = '_token'): self
    {
        if (preg_match('/^[A-Za-z0-9_-]+$/', $field) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid CSRF field name "%s".', $field));
        }
        $this->csrfToken = $token;
        $this->csrfField = $field;

        return $this;
    }

    /**
     * Base URL of the bundle (public/), to switch the base look in the preview. By default
     * it is the directory of the page's celerik.css.
     */
    public function assetsUrl(string $url): self
    {
        $this->assetsUrl = rtrim($url, '/');

        return $this;
    }

    /** @param array<string, string> $labels Keys from self::LABELS, or token names for their label. */
    public function labels(array $labels): self
    {
        foreach ($labels as $key => $text) {
            if (!isset(self::LABELS[$key]) && !isset(self::TOKENS[$key])) {
                throw new InvalidArgumentException(sprintf('Unknown theme builder label "%s".', $key));
            }
            $this->labels[$key] = $text;
        }

        return $this;
    }

    /**
     * The theme posted to saveUrl(): a JSON body (the Save button, {"theme": {...}}) or the
     * form fields (without JavaScript, theme[light][primary]…). Check the CSRF token first.
     *
     *     $theme = ThemeBuilder::fromRequest($_POST, file_get_contents('php://input'));
     *     file_put_contents('theme.json', $theme->toJson());
     *
     * @param array<mixed> $form The parsed form fields ($_POST, $request->request->all()).
     * @param string $body The raw request body, for JSON requests.
     * @throws InvalidArgumentException When the theme is missing or invalid: answer with HTTP 422.
     */
    public static function fromRequest(array $form, string $body = ''): Theme
    {
        if (trim($body) !== '' && in_array(trim($body)[0], ['{', '['], true)) {
            $data = json_decode($body, true, 8);
            if (!is_array($data)) {
                throw new InvalidArgumentException('The request body is not valid JSON.');
            }
            $theme = $data['theme'] ?? $data;
        } else {
            $theme = $form['theme'] ?? null;
        }
        if (!is_array($theme)) {
            throw new InvalidArgumentException('The request has no theme.');
        }

        // Form fields are strings: an empty optional field keeps the base look's value.
        foreach (['radius', 'fontFamily', 'shadow'] as $key) {
            if (($theme[$key] ?? null) === '') {
                unset($theme[$key]);
            }
        }
        if (isset($theme['radius']) && is_string($theme['radius'])) {
            if (!is_numeric($theme['radius'])) {
                throw new InvalidArgumentException('The radius must be a number (rem).');
            }
            $theme['radius'] = (float) $theme['radius'];
        }

        return Theme::fromArray($theme);
    }

    public function getTheme(): Theme
    {
        return $this->theme ??= Theme::preset('bootstrap');
    }

    public function toArray(): array
    {
        $presets = [];
        foreach (Theme::BASES as $base) {
            $presets[$base] = Theme::preset($base)->toArray();
        }

        return array_filter([
            'theme' => $this->getTheme()->toArray(),
            'presets' => $presets,
            'pairs' => Contrast::pairs(),
            'labels' => array_replace(self::LABELS, array_intersect_key($this->labels, self::LABELS)),
            'saveUrl' => $this->saveUrl,
            'csrf' => $this->csrfToken === null ? null : ['field' => $this->csrfField],
            'assetsUrl' => $this->assetsUrl,
        ], static fn (mixed $value): bool => $value !== null);
    }

    public function render(): string
    {
        $theme = $this->getTheme();
        $titleId = $this->id . '-title';

        $controls = Html::tag('h2', ['id' => $titleId, 'class' => 'h4'], $this->text('title'))
            . ($this->csrfToken === null ? '' : Html::tag('input', [
                'type' => 'hidden',
                'name' => $this->csrfField,
                'value' => $this->csrfToken,
            ]))
            . $this->generalFields($theme)
            . $this->palette($theme, 'light')
            . $this->palette($theme, 'dark')
            . $this->shapeFields($theme)
            . $this->report($theme)
            . $this->actions();

        $form = Html::tag('form', [
            'class' => 'celerik-theme-builder__controls',
            'method' => 'post',
            'action' => $this->saveUrl ?? '',
            'aria-labelledby' => $titleId,
            'novalidate' => true,
        ], $controls);

        return Html::tag('div', $this->rootAttributes(['celerik-theme-builder']), $form . $this->previewArea());
    }

    private function generalFields(Theme $theme): string
    {
        $baseOptions = '';
        foreach (self::BASE_NAMES as $value => $name) {
            $baseOptions .= $this->option($value, $name, $theme->getBase() === $value);
        }
        $presetOptions = '';
        foreach (self::BASE_NAMES as $value => $name) {
            $presetOptions .= $this->option($value, $name, false);
        }

        return $this->field('name', $this->text('name'), Html::tag('input', [
            'type' => 'text',
            'id' => $this->id . '-name',
            'name' => 'theme[name]',
            'class' => 'form-control',
            'value' => $theme->getName(),
            'required' => true,
            'maxlength' => 60,
            'pattern' => '[\p{L}\p{N} ._\-]+',
        ]))
            . $this->field('base', $this->text('base'), Html::tag('select', [
                'id' => $this->id . '-base',
                'name' => 'theme[base]',
                'class' => 'form-select',
            ], $baseOptions))
            . Html::tag('div', ['class' => 'mb-3 celerik-theme-builder__preset', 'hidden' => true], Html::tag(
                'label',
                ['for' => $this->id . '-preset', 'class' => 'form-label'],
                $this->text('preset'),
            ) . Html::tag('div', ['class' => 'input-group'], Html::tag('select', [
                'id' => $this->id . '-preset',
                'class' => 'form-select',
            ], $presetOptions) . Html::tag('button', [
                'type' => 'button',
                'class' => 'btn btn-secondary',
                'data-celerik-action' => 'preset',
            ], $this->text('loadPreset'))));
    }

    private function palette(Theme $theme, string $mode): string
    {
        $fields = '';
        foreach ($theme->palette($mode) as $token => $color) {
            $id = sprintf('%s-%s-%s', $this->id, $mode, $token);
            $label = $this->labels[$token] ?? self::TOKENS[$token][0];
            $fields .= Html::tag('div', ['class' => 'mb-2 celerik-theme-builder__color'], Html::tag(
                'label',
                ['for' => $id, 'class' => 'form-label', 'id' => $id . '-label'],
                Html::escape($label),
            ) . Html::tag('div', ['class' => 'input-group'], Html::tag('input', [
                'type' => 'color',
                'id' => $id . '-picker',
                'class' => 'form-control form-control-color',
                'value' => $color,
                'aria-label' => $label . ', ' . ($this->labels['picker'] ?? self::LABELS['picker']),
                'data-celerik-token' => $mode . '.' . $token,
                'tabindex' => '-1',
            ]) . Html::tag('input', [
                'type' => 'text',
                'id' => $id,
                'name' => sprintf('theme[%s][%s]', $mode, $token),
                'class' => 'form-control font-monospace',
                'value' => $color,
                'required' => true,
                'pattern' => '#[0-9a-fA-F]{6}',
                'maxlength' => 7,
                'spellcheck' => 'false',
                'autocomplete' => 'off',
                'aria-describedby' => $id . '-help',
                'data-celerik-token' => $mode . '.' . $token,
            ])) . Html::tag(
                'div',
                ['id' => $id . '-help', 'class' => 'form-text'],
                Html::escape(self::TOKENS[$token][1]),
            ));
        }

        return Html::tag(
            'fieldset',
            ['class' => 'mb-3 celerik-theme-builder__palette', 'data-celerik-mode' => $mode],
            Html::tag('legend', ['class' => 'h6'], $this->text($mode)) . $fields,
        );
    }

    private function shapeFields(Theme $theme): string
    {
        $radius = $theme->getRadius() ?? 0.375;
        $keepBase = $this->labels['keepBase'] ?? self::LABELS['keepBase'];
        $shadowOptions = $this->option('', $keepBase, $theme->getShadow() === null);
        foreach (self::SHADOW_NAMES as $value => $name) {
            $shadowOptions .= $this->option($value, $name, $theme->getShadow() === $value);
        }
        $fonts = '';
        foreach (self::FONTS as $font) {
            $fonts .= Html::tag('option', ['value' => $font], '');
        }

        $radiusField = Html::tag('div', ['class' => 'mb-3'], Html::tag(
            'label',
            ['for' => $this->id . '-radius', 'class' => 'form-label'],
            $this->text('radius'),
        ) . Html::tag('div', ['class' => 'd-flex align-items-center gap-2'], Html::tag('input', [
            'type' => 'range',
            'id' => $this->id . '-radius',
            'name' => 'theme[radius]',
            'class' => 'form-range',
            'min' => 0,
            'max' => 2,
            'step' => 0.125,
            'value' => $radius,
        ]) . Html::tag('output', [
            'for' => $this->id . '-radius',
            'class' => 'font-monospace',
            'id' => $this->id . '-radius-value',
        ], Html::escape(rtrim(rtrim(number_format($radius, 3, '.', ''), '0'), '.')))));

        $fontField = Html::tag('div', ['class' => 'mb-3'], Html::tag(
            'label',
            ['for' => $this->id . '-font', 'class' => 'form-label'],
            $this->text('font'),
        ) . Html::tag('input', [
            'type' => 'text',
            'id' => $this->id . '-font',
            'name' => 'theme[fontFamily]',
            'class' => 'form-control',
            'value' => $theme->getFontFamily(),
            'maxlength' => 200,
            'list' => $this->id . '-fonts',
            'aria-describedby' => $this->id . '-font-help',
        ]) . Html::tag('datalist', ['id' => $this->id . '-fonts'], $fonts)
            . Html::tag('div', ['id' => $this->id . '-font-help', 'class' => 'form-text'], $this->text('fontHelp')));

        $shadowField = $this->field('shadow', $this->text('shadow'), Html::tag('select', [
            'id' => $this->id . '-shadow',
            'name' => 'theme[shadow]',
            'class' => 'form-select',
        ], $shadowOptions));

        return Html::tag(
            'fieldset',
            ['class' => 'mb-3'],
            Html::tag('legend', ['class' => 'h6'], $this->text('shape')) . $radiusField . $fontField . $shadowField,
        );
    }

    private function report(Theme $theme): string
    {
        $results = Contrast::report($theme);
        $failures = count(array_filter($results, static fn (ContrastResult $result): bool => !$result->passes()));
        $summary = match ($failures) {
            0 => $this->labels['allPass'] ?? self::LABELS['allPass'],
            1 => $this->labels['oneFails'] ?? self::LABELS['oneFails'],
            default => str_replace(
                '{count}',
                (string) $failures,
                $this->labels['manyFail'] ?? self::LABELS['manyFail'],
            ),
        };

        $items = '';
        foreach ($results as $result) {
            $items .= $this->reportItem($result);
        }
        $summaryClass = $failures === 0 ? 'text-success-emphasis' : 'text-danger-emphasis';

        return Html::tag('section', [
            'class' => 'mb-3 celerik-theme-builder__report',
            'aria-labelledby' => $this->id . '-report-title',
        ], Html::tag('h3', ['id' => $this->id . '-report-title', 'class' => 'h6'], $this->text('report'))
            . Html::tag('p', [
                'class' => 'celerik-theme-builder__summary ' . $summaryClass,
                'role' => 'status',
            ], Html::escape($summary))
            . Html::tag('ul', ['class' => 'list-unstyled small mb-0'], $items));
    }

    private function reportItem(ContrastResult $result): string
    {
        $passes = $result->passes();

        return Html::tag('li', [
            'class' => 'celerik-theme-builder__pair ' . ($passes ? 'is-pass' : 'is-fail'),
            'data-celerik-pair' => $result->mode . '.' . $result->foreground . '.' . $result->background,
        ], Html::tag(
            'span',
            ['class' => 'badge ' . ($passes ? 'text-bg-success' : 'text-bg-danger')],
            $this->text($passes ? 'pass' : 'fail'),
        ) . ' ' . Html::escape(sprintf(
            '%s · %s: %.2f:1 (%s %s:1)',
            $this->text($result->mode === 'light' ? 'modeLight' : 'modeDark'),
            $result->label,
            floor($result->ratio * 100) / 100,
            $this->text('needs'),
            rtrim(rtrim(number_format($result->minimum, 1), '0'), '.'),
        )));
    }

    private function actions(): string
    {
        $button = fn (string $action, string $label): string => Html::tag('button', [
            'type' => 'button',
            'class' => 'btn btn-secondary',
            'data-celerik-action' => $action,
        ], $this->text($label));
        $importLabel = Html::tag(
            'label',
            ['class' => 'btn btn-secondary mb-0', 'for' => $this->id . '-import'],
            $this->text('import'),
        );

        // Bootstrap's d-flex (display: flex !important) would override [hidden]: the flex
        // layout goes on an inner element.
        $group = static fn (string $part, string $content): string => Html::tag(
            'div',
            ['class' => 'celerik-theme-builder__' . $part, 'hidden' => true],
            Html::tag('div', ['class' => 'd-flex flex-wrap gap-2 mb-2'], $content),
        );
        $history = $group('history', $button('undo', 'undo') . $button('reset', 'reset'));
        $exports = $group('export', $button('download-css', 'downloadCss')
            . $button('download-json', 'downloadJson')
            . $button('copy-php', 'copyPhp')
            . $importLabel
            . Html::tag('input', [
                'type' => 'file',
                'id' => $this->id . '-import',
                'class' => 'visually-hidden',
                'accept' => 'application/json,.json',
                'data-celerik-action' => 'import',
            ]));

        $save = $this->saveUrl === null
            ? ''
            : Html::tag('button', ['type' => 'submit', 'class' => 'btn btn-primary'], $this->text('save'));

        return $history
            . $exports
            . Html::tag('p', ['class' => 'celerik-theme-builder__message small mb-2', 'role' => 'status'], '')
            . $save;
    }

    private function previewArea(): string
    {
        $modes = '';
        foreach (['light' => 'modeLight', 'dark' => 'modeDark'] as $mode => $label) {
            $id = $this->id . '-preview-' . $mode;
            $modes .= Html::tag('input', [
                'type' => 'radio',
                'class' => 'btn-check',
                'name' => $this->id . '-preview-mode',
                'id' => $id,
                'value' => $mode,
                'checked' => $mode === 'light',
            ]) . Html::tag('label', ['class' => 'btn btn-secondary btn-sm', 'for' => $id], $this->text($label));
        }

        $items = '';
        foreach ($this->preview as $item) {
            $content = $item instanceof Stringable ? (string) $item : Html::escape($item);
            $items .= Html::tag('div', ['class' => 'mb-4'], $content);
        }

        return Html::tag('section', [
            'class' => 'celerik-theme-builder__preview',
            'aria-labelledby' => $this->id . '-preview-title',
        ], Html::tag('h2', ['id' => $this->id . '-preview-title', 'class' => 'h4'], $this->text('preview'))
            . Html::tag('fieldset', ['class' => 'mb-3 celerik-theme-builder__modes', 'hidden' => true], Html::tag(
                'legend',
                ['class' => 'visually-hidden'],
                $this->text('previewMode'),
            ) . Html::tag('div', ['class' => 'btn-group', 'role' => 'group'], $modes))
            . Html::tag('div', ['class' => 'celerik-theme-builder__canvas', 'data-bs-theme' => 'light'], $items));
    }

    private function field(string $key, string $label, string $control): string
    {
        return Html::tag('div', ['class' => 'mb-3'], Html::tag(
            'label',
            ['for' => $this->id . '-' . $key, 'class' => 'form-label'],
            $label,
        ) . $control);
    }

    private function option(string $value, string $text, bool $selected): string
    {
        return Html::tag('option', ['value' => $value, 'selected' => $selected], Html::escape($text));
    }

    /** An escaped label. */
    private function text(string $key): string
    {
        return Html::escape($this->labels[$key] ?? self::LABELS[$key]);
    }
}
