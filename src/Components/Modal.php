<?php

declare(strict_types=1);

namespace Celerik\Ui\Components;

use Celerik\Ui\Component;
use Celerik\Ui\Support\Html;
use InvalidArgumentException;
use Stringable;

/**
 * Bootstrap modal dialog. Bootstrap's JS (the page's bootstrap.bundle) handles opening,
 * focus trapping, Escape and returning focus to the trigger.
 *
 * Content: a string is escaped text; a Stringable (e.g. a Form, or Html::raw()) is HTML.
 */
final class Modal extends Component
{
    private const SIZES = [
        'sm' => 'modal-sm', 'lg' => 'modal-lg', 'xl' => 'modal-xl', 'fullscreen' => 'modal-fullscreen',
    ];
    private const VARIANTS = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark', 'link'];

    private string $title = '';

    private string|Stringable $body = '';

    /** @var list<array{label: string, variant: string, dismiss: bool, attributes: array<string, string|bool|null>}> */
    private array $buttons = [];

    private ?string $size = null;

    private bool $staticBackdrop = false;

    private bool $scrollable = false;

    private bool $centered = false;

    private bool $open = false;

    private string $closeLabel = 'Close';

    public static function name(): string
    {
        return 'modal';
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function body(string|Stringable $body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Adds a footer button. Extra attributes, e.g. ['form' => 'contact', 'type' => 'submit'],
     * let a footer button submit a form placed in the body.
     *
     * @param array<string, string|bool|null> $attributes
     */
    public function button(
        string $label,
        string $variant = 'primary',
        bool $dismiss = false,
        array $attributes = [],
    ): self {
        if (!in_array($variant, self::VARIANTS, true)) {
            throw new InvalidArgumentException(sprintf('Unknown button variant "%s".', $variant));
        }

        $this->buttons[] = [
            'label' => $label,
            'variant' => $variant,
            'dismiss' => $dismiss,
            'attributes' => $attributes,
        ];

        return $this;
    }

    /**
     * A secondary footer button that closes the modal.
     */
    public function cancelButton(string $label = 'Cancel'): self
    {
        return $this->button($label, 'secondary', true);
    }

    public function size(string $size): self
    {
        if (!isset(self::SIZES[$size])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown modal size "%s". Supported: %s.',
                $size,
                implode(', ', array_keys(self::SIZES)),
            ));
        }

        $this->size = $size;

        return $this;
    }

    /**
     * Clicking outside does not close the modal (use for forms, to avoid losing input).
     * Escape and the close button still do.
     */
    public function staticBackdrop(bool $static = true): self
    {
        $this->staticBackdrop = $static;

        return $this;
    }

    public function scrollable(bool $scrollable = true): self
    {
        $this->scrollable = $scrollable;

        return $this;
    }

    public function centered(bool $centered = true): self
    {
        $this->centered = $centered;

        return $this;
    }

    /**
     * Opens the modal when the page loads, e.g. to show a form again with server errors.
     */
    public function open(bool $open = true): self
    {
        $this->open = $open;

        return $this;
    }

    /**
     * Accessible label of the × close button.
     */
    public function closeLabel(string $label): self
    {
        $this->closeLabel = $label;

        return $this;
    }

    /**
     * A button that opens this modal.
     *
     * @param array<string, string|bool|null> $attributes
     */
    public function trigger(string $label, string $variant = 'primary', array $attributes = []): string
    {
        if (!in_array($variant, self::VARIANTS, true)) {
            throw new InvalidArgumentException(sprintf('Unknown button variant "%s".', $variant));
        }

        return Html::tag('button', array_merge($attributes, [
            'type' => 'button',
            'class' => 'btn btn-' . $variant,
            'data-bs-toggle' => 'modal',
            'data-bs-target' => '#' . $this->id,
            'aria-haspopup' => 'dialog',
        ]), Html::escape($label));
    }

    public function toArray(): array
    {
        return $this->open ? ['open' => true] : [];
    }

    public function render(): string
    {
        $titleId = $this->id . '-title';

        $header = Html::tag('div', ['class' => 'modal-header'], Html::tag(
            'h2',
            ['class' => 'modal-title fs-5', 'id' => $titleId],
            Html::escape($this->title),
        ) . Html::tag('button', [
            'type' => 'button',
            'class' => 'btn-close',
            'data-bs-dismiss' => 'modal',
            'aria-label' => $this->closeLabel,
        ], ''));

        $footer = '';
        if ($this->buttons !== []) {
            $buttons = '';
            foreach ($this->buttons as $button) {
                $buttons .= Html::tag('button', array_merge(['type' => 'button'], $button['attributes'], [
                    'class' => 'btn btn-' . $button['variant'],
                    'data-bs-dismiss' => $button['dismiss'] ? 'modal' : null,
                ]), Html::escape($button['label']));
            }
            $footer = Html::tag('div', ['class' => 'modal-footer'], $buttons);
        }

        $dialog = Html::tag('div', [
            'class' => Html::classes([
                'modal-dialog',
                $this->size === null ? null : self::SIZES[$this->size],
                $this->scrollable ? 'modal-dialog-scrollable' : null,
                $this->centered ? 'modal-dialog-centered' : null,
            ]),
        ], Html::tag(
            'div',
            ['class' => 'modal-content'],
            $header . Html::tag('div', ['class' => 'modal-body'], Html::content($this->body)) . $footer,
        ));

        return Html::tag('div', array_merge($this->rootAttributes(['modal', 'fade', 'celerik-modal']), [
            'tabindex' => '-1',
            'aria-labelledby' => $titleId,
            'aria-hidden' => 'true',
            // Escape keeps closing the dialog, as users of the WAI-ARIA dialog pattern expect.
            'data-bs-backdrop' => $this->staticBackdrop ? 'static' : null,
        ]), $dialog);
    }
}
