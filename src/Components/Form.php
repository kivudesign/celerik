<?php

declare(strict_types=1);

namespace Celerik\Ui\Components;

use Celerik\Ui\Component;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Form\Grid;
use Celerik\Ui\Components\Form\Repeater;
use Celerik\Ui\Components\Form\Section;
use Celerik\Ui\Components\Form\Wizard;
use Celerik\Ui\Support\Arr;
use Celerik\Ui\Support\Html;
use InvalidArgumentException;

/**
 * Bootstrap form with client-side validation (Parsley.js) and input masks (Inputmask).
 *
 * Client-side validation is a convenience only: always validate again on the server
 * and pass the messages back with errors().
 */
final class Form extends Component
{
    private const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    private string $action = '';

    private string $method = 'POST';

    private ?string $csrfToken = null;

    private string $csrfField = '_token';

    /** @var list<Field|Section|Repeater> */
    private array $items = [];

    private int $columns = 1;

    private string $breakpoint = 'md';

    private ?Wizard $wizard = null;

    private ?string $submitLabel = 'Submit';

    private bool $floatingLabels = false;

    /** @var array<string, string|list<string>> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $values = [];

    private ?string $errorSummaryTitle = null;

    public static function name(): string
    {
        return 'form';
    }

    public function action(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    /**
     * PUT, PATCH and DELETE are sent as POST with a `_method` field (Laravel and Symfony convention).
     */
    public function method(string $method): self
    {
        $method = strtoupper($method);

        if (!in_array($method, self::METHODS, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported form method "%s".', $method));
        }

        $this->method = $method;

        return $this;
    }

    public function csrf(string $token, string $field = '_token'): self
    {
        $this->csrfToken = $token;
        $this->csrfField = $field;

        return $this;
    }

    /**
     * Fields, sections that group them (Section::make(), Section::fieldset()) and
     * repeaters (Repeater::make()).
     */
    public function add(Field|Section|Repeater ...$items): self
    {
        array_push($this->items, ...$items);

        return $this;
    }

    /**
     * Lays the fields and sections out in 1, 2, 3, 4 or 6 columns from a Bootstrap
     * breakpoint up, one column below it. columnSpan() widens a field or section.
     */
    public function columns(int $columns, string $breakpoint = 'md'): self
    {
        Grid::assertColumns($columns, $breakpoint);
        $this->columns = $columns;
        $this->breakpoint = $breakpoint;

        return $this;
    }

    /**
     * Shows the form's sections one at a time, as the steps of a wizard, with a step
     * indicator and Back and Next buttons. Next checks the step's fields first; the submit
     * button is on the last step, and after a failed submit the form opens at the first step
     * with an error. Add Section::make() items (and hidden fields).
     *
     * @param string $stepOf the screen reader prefix of a step's heading (sprintf: step, count)
     */
    public function wizard(
        string $next = 'Next',
        string $previous = 'Back',
        string $progress = 'Progress',
        string $stepOf = 'Step %d of %d: ',
        string $completed = 'completed',
    ): self {
        $this->wizard = new Wizard($next, $previous, $progress, $stepOf, $completed);

        return $this;
    }

    /**
     * Every field, including those in sections, in order.
     *
     * @return list<Field>
     */
    public function getFields(): array
    {
        $this->fillRepeaters();

        return Section::make('')->add(...$this->items)->getFields();
    }

    /**
     * Bootstrap floating labels for text inputs, selects and textareas (the Material look).
     */
    public function floatingLabels(bool $floating = true): self
    {
        $this->floatingLabels = $floating;

        return $this;
    }

    /**
     * Label of the submit button; null renders no button.
     */
    public function submit(?string $label): self
    {
        $this->submitLabel = $label;

        return $this;
    }

    /**
     * Lists every error at the top of the form, with a link to each field, and moves focus
     * to the list after a failed submit (server-side or in the browser). Screen readers then
     * announce the problems once, instead of one live region per field (WCAG 3.3.1).
     * The messages next to the fields stay.
     */
    public function errorSummary(bool $enabled = true, string $title = 'There is a problem'): self
    {
        $this->errorSummaryTitle = $enabled ? $title : null;

        return $this;
    }

    /**
     * Server-side validation errors, field name => message or list of messages.
     *
     * @param array<string, string|list<string>> $errors
     */
    public function errors(array $errors): self
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Previously submitted values (old input), field name => value.
     *
     * @param array<string, mixed> $values
     */
    public function values(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            // Name of the hidden CSRF input, which async uploads send as a header.
            'csrf' => $this->csrfToken === null || $this->method === 'GET' ? null : ['field' => $this->csrfField],
            'errorSummary' => $this->errorSummaryTitle === null ? null : true,
            'options' => $this->options,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    public function render(): string
    {
        // Old input decides a repeater's rows, which the error summary lists too.
        $this->fillRepeaters();
        $spoofed = !in_array($this->method, ['GET', 'POST'], true);
        $content = '';

        if ($this->csrfToken !== null && $this->method !== 'GET') {
            $content .= Html::tag('input', [
                'type' => 'hidden',
                'name' => $this->csrfField,
                'value' => $this->csrfToken,
            ]);
        }

        if ($spoofed) {
            $content .= Html::tag('input', ['type' => 'hidden', 'name' => '_method', 'value' => $this->method]);
        }

        if ($this->errorSummaryTitle !== null) {
            $content .= $this->renderErrorSummary();
        }

        $renderOne = fn (Field $field): string => $field->render(
            $this->id,
            $this->errorFor($field),
            $this->valueFor($field),
            $this->floatingLabels,
        );
        $renderField = fn (Field|Repeater $item): string => $item instanceof Repeater
            ? $item->render($this->id, $renderOne, $this->errorFor($item))
            : $renderOne($item);
        $hasError = fn (Field|Repeater $item): bool => $this->errorFor($item) !== null;
        if ($this->wizard !== null) {
            $content .= $this->wizard->render($this->id, $this->items, $renderField, $hasError, $this->submitLabel);
        } else {
            $content .= Grid::render(
                $this->items,
                $this->columns,
                $this->breakpoint,
                fn (Field|Section|Repeater $item): string => $item instanceof Section
                    ? $item->render($this->id, $renderField, $hasError)
                    : $renderField($item),
            );
        }

        if ($this->submitLabel !== null && $this->wizard === null) {
            $content .= Html::tag(
                'button',
                ['type' => 'submit', 'class' => 'btn btn-primary'],
                Html::escape($this->submitLabel),
            );
        }

        $classes = $this->wizard === null ? ['celerik-form'] : ['celerik-form', 'celerik-wizard'];
        $attributes = array_merge($this->rootAttributes($classes), [
            'action' => $this->action,
            'method' => $spoofed ? 'POST' : $this->method,
            'enctype' => $this->hasFileField() ? 'multipart/form-data' : null,
            'novalidate' => true,
        ]);

        return Html::tag('form', $attributes, $content);
    }

    private function hasFileField(): bool
    {
        foreach ($this->getFields() as $field) {
            if ($field->isFile()) {
                return true;
            }
        }

        return false;
    }

    private function renderErrorSummary(): string
    {
        $titleId = $this->id . '-error-summary-title';
        $items = '';
        foreach ($this->summaryTargets($this->items) as $field) {
            $error = $this->errorFor($field);
            if ($error !== null) {
                $href = '#' . Field::fieldId($this->id, $field->getName());
                $link = Html::tag('a', ['href' => $href, 'class' => 'alert-link'], Html::escape($error));
                $items .= Html::tag('li', [], $link);
            }
        }

        // Hidden until there are errors; form.js fills it after a failed browser validation.
        return Html::tag('div', [
            'id' => $this->id . '-error-summary',
            'class' => 'alert alert-danger celerik-error-summary',
            'tabindex' => '-1',
            'aria-labelledby' => $titleId,
            'hidden' => $items === '',
        ], Html::tag('h2', ['id' => $titleId, 'class' => 'h6'], Html::escape((string) $this->errorSummaryTitle))
            . Html::tag('ul', ['class' => 'mb-0'], $items));
    }

    /**
     * The fields and repeaters that can have an error, in order.
     *
     * @param list<Field|Section|Repeater> $items
     *
     * @return list<Field|Repeater>
     */
    private function summaryTargets(array $items): array
    {
        $targets = [];
        foreach ($items as $item) {
            array_push($targets, ...match (true) {
                $item instanceof Section => $this->summaryTargets($item->getItems()),
                $item instanceof Repeater => [$item, ...$item->getFields()],
                default => [$item],
            });
        }

        return $targets;
    }

    /**
     * Gives each repeater its old input, which decides its rows.
     *
     * @param list<Field|Section|Repeater>|null $items
     */
    private function fillRepeaters(?array $items = null): void
    {
        foreach ($items ?? $this->items as $item) {
            if ($item instanceof Section) {
                $this->fillRepeaters($item->getItems());
            } elseif ($item instanceof Repeater) {
                $item->fill(Arr::get($this->values, $item->getName()));
            }
        }
    }

    private function errorFor(Field|Repeater $field): ?string
    {
        $key = $field instanceof Repeater ? $field->getName() : $this->key($field);
        $error = $this->errors[$key] ?? null;
        // A field that submits an array may get an error on one of its items
        // (Laravel: "tags.1", "stay.end", "photos.0"). A repeater's rows show their own.
        if ($error === null && $field instanceof Field && $field->submitsArray()) {
            foreach ($this->errors as $name => $message) {
                if (str_starts_with((string) $name, $key . '.')) {
                    $error = $message;
                    break;
                }
            }
        }

        if (is_array($error)) {
            $error = $error[0] ?? null;
        }

        return is_string($error) ? $error : null;
    }

    private function valueFor(Field $field): mixed
    {
        if ($field->getType() === 'password') {
            return null;
        }

        return Arr::get($this->values, $this->key($field));
    }

    /**
     * Maps `address[city]` to the `address.city` key used by error bags and old input.
     */
    private function key(Field $field): string
    {
        return rtrim(str_replace(['[]', '[', ']'], ['', '.', ''], $field->getName()), '.');
    }
}
