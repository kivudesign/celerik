<?php

declare(strict_types=1);

namespace Celerik\Ui\Components\Form;

use Celerik\Ui\Support\Html;
use Closure;
use InvalidArgumentException;
use LogicException;

/**
 * A group of fields the user repeats: order lines, addresses, contacts. Each row submits
 * as `name[key][field]`; the user adds, removes and (with reorderable()) moves rows.
 *
 * Row keys are kept: after a failed submit, old input and errors such as `lines.2.qty`
 * find their row. They are not renumbered, so use array_values() if you need a list.
 */
final class Repeater
{
    /** The row key in the template form.js copies for a new row. */
    public const PLACEHOLDER = '__index__';

    /** Fields form.js cannot set up in a row added in the browser. */
    private const UNSUPPORTED = ['file', 'editor', 'hidden'];

    /** @var list<Field> */
    private array $schema = [];

    private int $columns = 1;

    private string $breakpoint = 'md';

    private int|string|null $columnSpan = null;

    private int $min = 0;

    private ?int $max = null;

    private int $defaultItems = 1;

    private bool $reorderable = false;

    private ?string $help = null;

    /** @var array<int|string, mixed>|null rows from value() */
    private ?array $value = null;

    /** @var array<int|string, mixed>|null rows from the form's old input */
    private ?array $filled = null;

    /** @var array{add: string, remove: string, up: string, down: string, item: string} */
    private array $labels = [
        'add' => 'Add',
        'remove' => 'Remove',
        'up' => 'Move up',
        'down' => 'Move down',
        'item' => 'Item %d',
    ];

    private function __construct(private readonly string $name, private readonly string $label)
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_\-]*$/', $name) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid repeater name "%s".', $name));
        }
    }

    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? ucfirst(str_replace(['_', '-'], ' ', $name)));
    }

    /**
     * The fields of one row. Their names are relative to the row: `product` submits as
     * `lines[0][product]`. File, rich text and hidden fields are not supported in rows.
     */
    public function schema(Field ...$fields): self
    {
        foreach ($fields as $field) {
            if (in_array($field->getType(), self::UNSUPPORTED, true)) {
                throw new InvalidArgumentException(sprintf(
                    'A repeater row cannot hold a %s field ("%s").',
                    $field->getType(),
                    $field->getName(),
                ));
            }
        }

        $this->schema = array_values($fields);

        return $this;
    }

    /**
     * Lays a row's fields out in 1, 2, 3, 4 or 6 columns from a Bootstrap breakpoint up.
     */
    public function columns(int $columns, string $breakpoint = 'md'): self
    {
        Grid::assertColumns($columns, $breakpoint);
        $this->columns = $columns;
        $this->breakpoint = $breakpoint;

        return $this;
    }

    /**
     * How many columns of its form or section the repeater takes: a number, or "full".
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
     * The fewest rows: Remove is disabled at this count. Check it on the server too.
     */
    public function min(int $min): self
    {
        if ($min < 0 || ($this->max !== null && $min > $this->max)) {
            throw new InvalidArgumentException('A repeater\'s min is from 0 up to its max.');
        }

        $this->min = $min;

        return $this;
    }

    /**
     * The most rows: Add is disabled at this count. Check it on the server too.
     */
    public function max(?int $max): self
    {
        if ($max !== null && ($max < 1 || $max < $this->min)) {
            throw new InvalidArgumentException('A repeater\'s max is from 1, and at least its min.');
        }

        $this->max = $max;

        return $this;
    }

    /**
     * How many empty rows a new form starts with (at least min()).
     */
    public function defaultItems(int $count): self
    {
        if ($count < 0) {
            throw new InvalidArgumentException('A repeater cannot start with fewer than 0 rows.');
        }

        $this->defaultItems = $count;

        return $this;
    }

    /**
     * Move up and Move down buttons on each row. The rows submit in their order on screen.
     */
    public function reorderable(bool $reorderable = true): self
    {
        $this->reorderable = $reorderable;

        return $this;
    }

    public function help(?string $help): self
    {
        $this->help = $help;

        return $this;
    }

    /**
     * The button texts and each row's name; `item` takes the row's position (sprintf).
     */
    public function labels(
        ?string $add = null,
        ?string $remove = null,
        ?string $up = null,
        ?string $down = null,
        ?string $item = null,
    ): self {
        $this->labels = [
            'add' => $add ?? $this->labels['add'],
            'remove' => $remove ?? $this->labels['remove'],
            'up' => $up ?? $this->labels['up'],
            'down' => $down ?? $this->labels['down'],
            'item' => $item ?? $this->labels['item'],
        ];

        return $this;
    }

    /**
     * The rows to show: a list of `field => value` arrays (e.g. the record's lines).
     *
     * @param array<int|string, array<string, mixed>> $rows
     */
    public function value(array $rows): self
    {
        $this->value = $rows;

        return $this;
    }

    /**
     * The form's old input for this repeater, which wins over value().
     *
     * @internal
     */
    public function fill(mixed $rows): void
    {
        $this->filled = is_array($rows) ? $rows : null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * The fields of every row, named for their row (`lines[0][product]`), in order.
     *
     * @return list<Field>
     */
    public function getFields(): array
    {
        $fields = [];
        foreach ($this->rows() as $key => $row) {
            array_push($fields, ...$this->rowFields((string) $key, $row));
        }

        return $fields;
    }

    /**
     * @param Closure(Field): string $renderField the form renders each field, with its error and value
     */
    public function render(string $formId, Closure $renderField, ?string $error): string
    {
        $id = Field::fieldId($formId, $this->name);
        $rows = $this->rows();
        $count = count($rows);
        $helpId = $this->help === null ? null : $id . '-help';
        $errorId = $error === null ? null : $id . '-error';

        $items = '';
        $position = 0;
        foreach ($rows as $key => $row) {
            $items .= $this->renderRow($formId, (string) $key, ++$position, $count, $renderField, $row);
        }

        // The template form.js copies for a new row: fields without values or errors.
        $template = Html::tag(
            'template',
            [],
            $this->renderRow(
                $formId,
                self::PLACEHOLDER,
                0,
                $count + 1,
                static fn (Field $field): string => $field->render($formId),
            ),
        );

        $legend = Html::tag(
            'legend',
            ['class' => 'form-label fs-6'],
            Html::escape($this->label) . ($this->min > 0 ? Field::REQUIRED_MARKER : ''),
        );
        $add = Html::tag('button', [
            'type' => 'button',
            'class' => 'btn btn-sm btn-outline-primary',
            'data-celerik-repeater-add' => true,
            'disabled' => $this->max !== null && $count >= $this->max,
        ], Html::escape($this->labels['add']));

        $fieldset = Html::tag('fieldset', ['aria-describedby' => Html::classes([$helpId, $errorId])], $legend
            . Html::tag('ol', ['class' => 'celerik-repeater-items'], $items)
            . $add . $template);

        $feedback = $errorId === null ? '' : Html::tag(
            'div',
            ['id' => $errorId, 'class' => 'invalid-feedback d-block celerik-server-error'],
            Html::escape($error),
        );
        $help = $helpId === null
            ? ''
            : Html::tag('div', ['id' => $helpId, 'class' => 'form-text'], Html::escape($this->help));

        return Html::tag('div', [
            'id' => $id,
            'class' => 'mb-3 celerik-repeater',
            // An error summary link to the repeater ("add at least one line") focuses it.
            'tabindex' => '-1',
            'data-celerik-repeater' => json_encode([
                'min' => $this->min,
                'max' => $this->max,
                'item' => $this->labels['item'],
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ], $fieldset . $feedback . $help);
    }

    /**
     * The rows, by key: old input, then value(), then empty rows.
     *
     * @return array<int|string, mixed>
     */
    private function rows(): array
    {
        $rows = $this->filled ?? $this->value;
        if ($rows === null) {
            $rows = array_fill(0, max($this->defaultItems, $this->min), []);
        }

        if ($this->schema === []) {
            throw new LogicException(sprintf('Repeater "%s" has no schema() fields.', $this->name));
        }

        // Old input comes from the request: keep only keys that make valid field names.
        return array_filter(
            $rows,
            static fn (int|string $key): bool => preg_match('/^[A-Za-z0-9_-]+$/', (string) $key) === 1,
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * @return list<Field>
     */
    private function rowFields(string $key, mixed $row): array
    {
        return array_map(function (Field $field) use ($key, $row): Field {
            $copy = $field->withName($this->name . '[' . $key . '][' . $field->getName() . ']');
            $value = is_array($row) ? ($row[$field->getName()] ?? null) : null;

            return $value === null ? $copy : $copy->value($value);
        }, $this->schema);
    }

    /**
     * @param Closure(Field): string $renderField
     */
    private function renderRow(
        string $formId,
        string $key,
        int $position,
        int $count,
        Closure $renderField,
        mixed $row = null,
    ): string {
        $buttons = '';
        if ($this->reorderable) {
            foreach (['up' => 'M8 12V4M4 8l4-4 4 4', 'down' => 'M8 4v8M4 8l4 4 4-4'] as $direction => $path) {
                $icon = '<svg aria-hidden="true" width="16" height="16" viewBox="0 0 16 16" fill="none"'
                    . ' stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
                    . '<path d="' . $path . '"/></svg>';
                $buttons .= Html::tag('button', [
                    'type' => 'button',
                    'class' => 'btn btn-sm btn-outline-secondary',
                    'data-celerik-repeater-move' => $direction,
                    'disabled' => $direction === 'up' ? $position <= 1 : $position >= $count,
                ], $icon . Html::tag('span', ['class' => 'visually-hidden'], Html::escape($this->labels[$direction])));
            }
        }
        $buttons .= Html::tag('button', [
            'type' => 'button',
            'class' => 'btn btn-sm btn-outline-danger',
            'data-celerik-repeater-remove' => true,
            'disabled' => $count <= $this->min,
        ], Html::escape($this->labels['remove']));

        $legend = Html::tag(
            'legend',
            ['class' => 'celerik-repeater-label'],
            Html::escape(sprintf($this->labels['item'], $position)),
        );
        $fields = Grid::render(
            $this->rowFields($key, $row),
            $this->columns,
            $this->breakpoint,
            static fn (Field|Section|Repeater $field): string => $field instanceof Field ? $renderField($field) : '',
        );

        return Html::tag('li', [
            'class' => 'card celerik-repeater-item',
            'data-celerik-key' => $key,
        ], Html::tag('fieldset', ['class' => 'card-body'], $legend
            . Html::tag('div', ['class' => 'celerik-repeater-actions'], $buttons)
            . $fields));
    }
}
