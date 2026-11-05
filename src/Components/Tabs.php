<?php

declare(strict_types=1);

namespace Celerik\Ui\Components;

use Celerik\Ui\Component;
use Celerik\Ui\Support\Html;
use InvalidArgumentException;
use Stringable;

/**
 * Bootstrap tabs following the WAI-ARIA tabs pattern (tablist, tab, tabpanel). Bootstrap's
 * JS switches panels and handles arrow-key navigation.
 *
 * Content: a string is escaped text; a Stringable (e.g. a Grid, or Html::raw()) is HTML.
 */
final class Tabs extends Component
{
    private const STYLES = ['tabs', 'pills', 'underline'];

    /** @var array<string, array{label: string, content: string|Stringable}> */
    private array $tabs = [];

    private ?string $active = null;

    private string $style = 'tabs';

    private bool $fill = false;

    private bool $rememberInHash = false;

    private string $label = '';

    public static function name(): string
    {
        return 'tabs';
    }

    public function tab(string $id, string $label, string|Stringable $content): self
    {
        if (preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $id) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid tab id "%s".', $id));
        }

        if (isset($this->tabs[$id])) {
            throw new InvalidArgumentException(sprintf('Duplicate tab id "%s".', $id));
        }

        $this->tabs[$id] = ['label' => $label, 'content' => $content];

        return $this;
    }

    /**
     * Initially selected tab (defaults to the first one).
     */
    public function active(string $id): self
    {
        $this->active = $id;

        return $this;
    }

    /**
     * "tabs" (default), "pills" or "underline".
     */
    public function style(string $style): self
    {
        if (!in_array($style, self::STYLES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown tab style "%s". Supported: %s.',
                $style,
                implode(', ', self::STYLES),
            ));
        }

        $this->style = $style;

        return $this;
    }

    /**
     * Tabs share the full width.
     */
    public function fill(bool $fill = true): self
    {
        $this->fill = $fill;

        return $this;
    }

    /**
     * Keeps the selected tab in the URL hash (#profile), so links and reloads open it.
     */
    public function rememberInHash(bool $remember = true): self
    {
        $this->rememberInHash = $remember;

        return $this;
    }

    /**
     * Accessible name of the tab list (aria-label), e.g. "Account settings".
     */
    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function toArray(): array
    {
        return $this->rememberInHash ? ['rememberInHash' => true] : [];
    }

    public function render(): string
    {
        if ($this->tabs === []) {
            throw new InvalidArgumentException(sprintf('Tabs "%s" has no tabs.', $this->id));
        }

        $active = $this->active ?? array_key_first($this->tabs);
        if (!isset($this->tabs[$active])) {
            throw new InvalidArgumentException(sprintf('Unknown active tab "%s".', $active));
        }

        $buttons = '';
        $panels = '';

        foreach ($this->tabs as $id => $tab) {
            $isActive = $id === $active;
            $tabId = $this->id . '-' . $id . '-tab';
            $panelId = $this->id . '-' . $id;

            $button = Html::tag('button', [
                'type' => 'button',
                'class' => Html::classes(['nav-link', $this->fill ? 'w-100' : null, $isActive ? 'active' : null]),
                'id' => $tabId,
                'data-bs-toggle' => 'tab',
                'data-bs-target' => '#' . $panelId,
                'data-celerik-tab' => $id,
                'role' => 'tab',
                'aria-controls' => $panelId,
                'aria-selected' => $isActive ? 'true' : 'false',
                // Only the selected tab is in the Tab order; arrow keys move between tabs.
                'tabindex' => $isActive ? null : '-1',
            ], Html::escape($tab['label']));

            $buttons .= Html::tag('li', [
                'class' => Html::classes(['nav-item', $this->fill ? 'flex-fill' : null]),
                'role' => 'presentation',
            ], $button);

            $panels .= Html::tag('div', [
                'class' => Html::classes(['tab-pane', 'fade', $isActive ? 'show active' : null]),
                'id' => $panelId,
                'role' => 'tabpanel',
                'aria-labelledby' => $tabId,
                'tabindex' => '0',
            ], Html::content($tab['content']));
        }

        $list = Html::tag('ul', [
            'class' => Html::classes(['nav', 'nav-' . $this->style, 'mb-3']),
            'role' => 'tablist',
            'aria-label' => $this->label === '' ? null : $this->label,
        ], $buttons);

        return Html::tag(
            'div',
            $this->rootAttributes(['celerik-tabs']),
            $list . Html::tag('div', ['class' => 'tab-content'], $panels),
        );
    }
}
