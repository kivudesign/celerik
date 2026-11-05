<?php

declare(strict_types=1);

namespace Celerik\Ui\Components;

use Celerik\Ui\Component;
use Celerik\Ui\Components\Form\Field;
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

    /** @var list<Field> */
    private array $fields = [];

    private ?string $submitLabel = 'Submit';

    private bool $floatingLabels = false;

    /** @var array<string, string|list<string>> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $values = [];

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

    public function add(Field ...$fields): self
    {
        array_push($this->fields, ...$fields);

        return $this;
    }

    /**
     * @return list<Field>
     */
    public function getFields(): array
    {
        return $this->fields;
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
            'options' => $this->options,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    public function render(): string
    {
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

        foreach ($this->fields as $field) {
            $content .= $field->render(
                $this->id,
                $this->errorFor($field),
                $this->valueFor($field),
                $this->floatingLabels,
            );
        }

        if ($this->submitLabel !== null) {
            $content .= Html::tag(
                'button',
                ['type' => 'submit', 'class' => 'btn btn-primary'],
                Html::escape($this->submitLabel),
            );
        }

        $attributes = array_merge($this->rootAttributes(['celerik-form']), [
            'action' => $this->action,
            'method' => $spoofed ? 'POST' : $this->method,
            'enctype' => $this->hasFileField() ? 'multipart/form-data' : null,
            'novalidate' => true,
        ]);

        return Html::tag('form', $attributes, $content);
    }

    private function hasFileField(): bool
    {
        foreach ($this->fields as $field) {
            if ($field->isFile()) {
                return true;
            }
        }

        return false;
    }

    private function errorFor(Field $field): ?string
    {
        $error = $this->errors[$this->key($field)] ?? null;

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
