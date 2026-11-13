<?php

declare(strict_types=1);

namespace Celerik\Ui\Components\Form;

use Celerik\Ui\Support\Html;
use Closure;
use LogicException;

/**
 * Groups the fields of a form: a section (a card with a heading, optionally collapsible) or
 * a fieldset (a border and a legend, for controls that belong together, such as an address).
 * Both can lay their fields out in columns and hold other sections.
 */
final class Section
{
    /** @var list<Field|Section> */
    private array $items = [];

    private ?string $description = null;

    private int $columns = 1;

    private string $breakpoint = 'md';

    private int|string|null $columnSpan = null;

    private bool $collapsible = false;

    private bool $collapsed = false;

    private ?string $id = null;

    private function __construct(private readonly string $heading, private readonly bool $fieldset)
    {
    }

    /**
     * A card with a heading (h2, h3 in a section).
     */
    public static function make(string $heading): self
    {
        return new self($heading, false);
    }

    /**
     * A fieldset whose legend names its controls for screen readers.
     */
    public static function fieldset(string $legend): self
    {
        return new self($legend, true);
    }

    public function add(Field|Section ...$items): self
    {
        array_push($this->items, ...$items);

        return $this;
    }

    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Lays the items out in 1, 2, 3, 4 or 6 columns from a Bootstrap breakpoint up; one
     * column below it.
     */
    public function columns(int $columns, string $breakpoint = 'md'): self
    {
        Grid::assertColumns($columns, $breakpoint);
        $this->columns = $columns;
        $this->breakpoint = $breakpoint;

        return $this;
    }

    /**
     * How many columns of its form or parent section it takes: a number, or "full".
     */
    public function columnSpan(int|string $span): self
    {
        Grid::assertSpan($span);
        $this->columnSpan = $span;

        return $this;
    }

    public function getColumnSpan(): int|string|null
    {
        return $this->columnSpan;
    }

    /**
     * A heading the user can click to show or hide the section (a native <details>).
     * It opens by itself when a field inside it has an error.
     */
    public function collapsible(bool $collapsed = false): self
    {
        if ($this->fieldset) {
            throw new LogicException('A fieldset cannot be collapsible: use Section::make().');
        }

        $this->collapsible = true;
        $this->collapsed = $collapsed;

        return $this;
    }

    /**
     * The section's id; by default, the form's id and the heading.
     */
    public function id(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * The fields of the section and its sections, in order.
     *
     * @return list<Field>
     */
    public function getFields(): array
    {
        $fields = [];
        foreach ($this->items as $item) {
            array_push($fields, ...($item instanceof self ? $item->getFields() : [$item]));
        }

        return $fields;
    }

    /**
     * @param Closure(Field): string $renderField the form renders each field, with its error and value
     * @param Closure(Field): bool   $hasError
     * @param int                    $level       the heading level: 2 in a form, 3 in a section
     * @param string|null            $stepLabel   a wizard step's "Step 1 of 3: ", for screen readers
     */
    public function render(
        string $formId,
        Closure $renderField,
        Closure $hasError,
        int $level = 2,
        ?string $stepLabel = null,
    ): string {
        $id = $this->id ?? Field::fieldId($formId, 'section-' . $this->heading);
        $descriptionId = $this->description === null ? null : $id . '-description';
        $description = $descriptionId === null ? '' : Html::tag(
            'p',
            ['id' => $descriptionId, 'class' => 'form-text mt-0 mb-3'],
            Html::escape($this->description),
        );
        $content = Grid::render(
            $this->items,
            $this->columns,
            $this->breakpoint,
            fn (Field|Section $item): string => $item instanceof self
                ? $item->render($formId, $renderField, $hasError, min($level + 1, 6))
                : $renderField($item),
        );

        if ($this->fieldset) {
            $legend = Html::tag('legend', ['class' => 'celerik-form-legend'], Html::escape($this->heading));

            return Html::tag('fieldset', [
                'id' => $id,
                'class' => 'celerik-form-fieldset mb-3',
                'aria-describedby' => $descriptionId,
            ], $legend . $description . $content);
        }

        $headingId = $id . '-heading';
        $body = $description . $content;

        if ($this->collapsible) {
            // The summary is the button: a heading inside it would lose its role.
            // (and must be the first child of <details>).
            $summary = Html::tag(
                'summary',
                ['id' => $headingId, 'class' => 'celerik-form-summary card-body h' . min($level + 3, 6)],
                Html::escape($this->heading),
            );
            $open = !$this->collapsed || $this->hasErrors($hasError);

            return Html::tag(
                'details',
                ['id' => $id, 'class' => 'card mb-4 celerik-form-section', 'open' => $open],
                $summary . Html::tag('div', [
                    'class' => 'card-body pt-0',
                    'role' => 'group',
                    'aria-labelledby' => $headingId,
                    'aria-describedby' => $descriptionId,
                ], $body),
            );
        }

        $prefix = $stepLabel === null
            ? ''
            : Html::tag('span', ['class' => 'visually-hidden'], Html::escape($stepLabel));
        $heading = Html::tag('h' . $level, [
            'id' => $headingId,
            'class' => 'card-title h' . min($level + 3, 6) . ($description === '' ? ' mb-3' : ' mb-1'),
            // form.js moves focus to a wizard step's heading when the step changes.
            'tabindex' => $stepLabel === null ? null : '-1',
        ], $prefix . Html::escape($this->heading));

        return Html::tag('section', [
            'id' => $id,
            'class' => 'card mb-4 celerik-form-section',
            'aria-labelledby' => $headingId,
            'aria-describedby' => $descriptionId,
        ], Html::tag('div', ['class' => 'card-body'], $heading . $body));
    }

    public function getHeading(): string
    {
        return $this->heading;
    }

    public function isFieldset(): bool
    {
        return $this->fieldset;
    }

    public function isCollapsible(): bool
    {
        return $this->collapsible;
    }

    /**
     * Whether a field of the section, or of its sections, has an error.
     *
     * @param Closure(Field): bool $hasError
     */
    public function hasErrors(Closure $hasError): bool
    {
        foreach ($this->getFields() as $field) {
            if ($hasError($field)) {
                return true;
            }
        }

        return false;
    }
}
