<?php

declare(strict_types=1);

namespace Celerik\Ui\Components\Form;

use Celerik\Ui\Support\Arr;
use Celerik\Ui\Support\Html;
use Celerik\Ui\Upload\UploadRules;
use InvalidArgumentException;

/**
 * A Bootstrap form control with validation rules.
 *
 * Rules are emitted as native HTML5 attributes (required, minlength, pattern...), which
 * Parsley.js reads on the client and browsers understand without JavaScript.
 */
final class Field
{
    private const INPUT_TYPES = [
        'text', 'email', 'password', 'number', 'tel', 'url', 'date', 'time', 'datetime-local', 'search', 'hidden',
    ];
    private const REQUIRED_MARKER = ' <span class="celerik-required" aria-hidden="true">*</span>';
    private const TOOLBARS = ['basic', 'standard', 'full'];

    private ?UploadRules $uploadRules = null;
    private ?string $uploadUrl = null;

    /** @var string|list<mixed> */
    private string|array $toolbar = 'standard';

    private ?string $placeholder = null;
    private ?string $help = null;
    private mixed $value = null;
    private bool $required = false;
    private ?string $equalTo = null;
    private ?string $mask = null;
    private ?string $message = null;

    /** @var array<string, string|int|float|bool|null> */
    private array $attributes = [];

    /**
     * @param array<string|int, string> $choices value => label, for select fields
     */
    private function __construct(
        private readonly string $type,
        private readonly string $name,
        private readonly string $label,
        private readonly array $choices = [],
    ) {
        if ($name === '' || preg_match('/^[A-Za-z_][A-Za-z0-9_\-\[\]]*$/', $name) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid field name "%s".', $name));
        }
    }

    public static function input(string $type, string $name, ?string $label = null): self
    {
        if (!in_array($type, self::INPUT_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported input type "%s".', $type));
        }

        return new self($type, $name, $label ?? self::humanize($name));
    }

    public static function text(string $name, ?string $label = null): self
    {
        return self::input('text', $name, $label);
    }

    public static function email(string $name, ?string $label = null): self
    {
        return self::input('email', $name, $label);
    }

    public static function password(string $name, ?string $label = null): self
    {
        return self::input('password', $name, $label);
    }

    public static function number(string $name, ?string $label = null): self
    {
        return self::input('number', $name, $label);
    }

    public static function tel(string $name, ?string $label = null): self
    {
        return self::input('tel', $name, $label);
    }

    public static function date(string $name, ?string $label = null): self
    {
        return self::input('date', $name, $label);
    }

    public static function hidden(string $name, string|int|float $value): self
    {
        return self::input('hidden', $name, $name)->value($value);
    }

    public static function textarea(string $name, ?string $label = null): self
    {
        return new self('textarea', $name, $label ?? self::humanize($name));
    }

    /**
     * @param array<string|int, string> $choices value => label
     */
    public static function select(string $name, array $choices, ?string $label = null): self
    {
        return new self('select', $name, $label ?? self::humanize($name), $choices);
    }

    public static function checkbox(string $name, ?string $label = null): self
    {
        return new self('checkbox', $name, $label ?? self::humanize($name));
    }

    /**
     * File upload. Without upload() it is a plain file input (the form switches to
     * multipart); with upload() FilePond sends each file to that endpoint first.
     */
    public static function file(string $name, ?string $label = null): self
    {
        return (new self('file', $name, $label ?? self::humanize($name)))->rules(UploadRules::images());
    }

    /**
     * Rich text editor (Quill). Submits HTML: always sanitize it on the server
     * (see EditorHtml::sanitize()).
     */
    public static function editor(string $name, ?string $label = null): self
    {
        return new self('editor', $name, $label ?? self::humanize($name));
    }

    /**
     * Accepted files for a file field (defaults to UploadRules::images()). Use the same
     * rules in the UploadHandler.
     */
    public function rules(UploadRules $rules): self
    {
        $this->assertType('file', 'rules');
        $this->uploadRules = $rules;

        return $this;
    }

    /**
     * Uploads each file asynchronously to this endpoint (FilePond server protocol: POST
     * returns the id as text/plain, DELETE with the id as body reverts it). The form then
     * submits the ids; claim them with UploadHandler::claim().
     */
    public function upload(string $url): self
    {
        $this->assertType('file', 'upload');
        $this->uploadUrl = $url;

        return $this;
    }

    /**
     * Editor toolbar: "basic", "standard" (default), "full", or a Quill toolbar array.
     *
     * @param string|list<mixed> $toolbar
     */
    public function toolbar(string|array $toolbar): self
    {
        $this->assertType('editor', 'toolbar');

        if (is_string($toolbar) && !in_array($toolbar, self::TOOLBARS, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown toolbar "%s". Use %s or a Quill toolbar array.',
                $toolbar,
                implode(', ', self::TOOLBARS),
            ));
        }

        $this->toolbar = $toolbar;

        return $this;
    }

    public function required(bool $required = true): self
    {
        $this->required = $required;

        return $this;
    }

    public function minLength(int $length): self
    {
        return $this->attribute('minlength', $length);
    }

    public function maxLength(int $length): self
    {
        return $this->attribute('maxlength', $length);
    }

    public function min(int|float|string $min): self
    {
        return $this->attribute('min', $min);
    }

    public function max(int|float|string $max): self
    {
        return $this->attribute('max', $max);
    }

    /**
     * HTML5 pattern (anchored implicitly, no delimiters), e.g. `[A-Z]{2}[0-9]{4}`.
     */
    public function pattern(string $pattern): self
    {
        return $this->attribute('pattern', $pattern);
    }

    /**
     * Requires the same value as another field of the form (password confirmation).
     */
    public function equalTo(string $fieldName): self
    {
        $this->equalTo = $fieldName;

        return $this;
    }

    /**
     * Inputmask.js mask, e.g. `99/99/9999` or `(999) 999-9999`.
     */
    public function mask(string $mask): self
    {
        $this->mask = $mask;

        return $this;
    }

    /**
     * Custom client-side error message for every rule of this field.
     */
    public function message(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function help(string $help): self
    {
        $this->help = $help;

        return $this;
    }

    public function value(mixed $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function attribute(string $name, string|int|float|bool|null $value = true): self
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string|null $error Server-side error message to display.
     * @param mixed $value Submitted value, takes precedence over the field default.
     */
    public function render(string $formId, ?string $error = null, mixed $value = null): string
    {
        $id = self::fieldId($formId, $this->name);
        $value ??= $this->value;

        if ($this->type === 'hidden') {
            return Html::tag('input', [
                'type' => 'hidden',
                'id' => $id,
                'name' => $this->name,
                'value' => Arr::toText($value),
            ]);
        }

        $helpText = $this->help ?? ($this->type === 'file' ? $this->uploadRules?->describe() : null);
        $helpId = $helpText === null ? null : $id . '-help';
        $errorId = $error === null ? null : $id . '-error';
        $asyncUpload = $this->type === 'file' && $this->uploadUrl !== null;

        $attributes = array_merge($this->attributes, [
            'id' => $id,
            'name' => $this->name,
            // FilePond enforces "required" itself (see upload.js); the native attribute would block it.
            'required' => $this->required && !$asyncUpload,
            'placeholder' => $this->placeholder,
            'aria-describedby' => Html::classes([$helpId, $errorId]),
            'aria-invalid' => $error === null ? null : 'true',
            'data-parsley-equalto' => $this->equalTo === null ? null : '#' . self::fieldId($formId, $this->equalTo),
            'data-parsley-error-message' => $this->message,
            'data-celerik-mask' => $this->mask,
        ]);

        $control = match ($this->type) {
            'textarea' => Html::tag(
                'textarea',
                $attributes + ['class' => $this->controlClass('form-control', $error)],
                Html::escape(Arr::toText($value)),
            ),
            'select' => Html::tag(
                'select',
                $attributes + ['class' => $this->controlClass('form-select', $error)],
                $this->renderChoices($value),
            ),
            'file' => $this->renderFile($attributes, $error),
            'editor' => Html::tag(
                'textarea',
                $attributes + [
                    'class' => $this->controlClass('form-control celerik-editor-source', $error),
                    'rows' => 8,
                    'data-celerik-editor' => self::json([
                        'toolbar' => $this->toolbar,
                        'placeholder' => $this->placeholder,
                    ]),
                ],
                Html::escape(Arr::toText($value)),
            ),
            'checkbox' => Html::tag('input', $attributes + [
                'type' => 'checkbox',
                'value' => '1',
                'checked' => $value !== null && $value !== false && $value !== '' && $value !== '0',
                'class' => $this->controlClass('form-check-input', $error),
            ]),
            default => Html::tag('input', $attributes + [
                'type' => $this->type,
                'value' => $value === null ? null : Arr::toText($value),
                'class' => $this->controlClass('form-control', $error),
            ]),
        };

        $label = Html::tag(
            'label',
            [
                'for' => $id,
                // The editor's content area and FilePond's input are labelled by id (aria-labelledby).
                'id' => in_array($this->type, ['editor', 'file'], true) ? $id . '-label' : null,
                'class' => $this->type === 'checkbox' ? 'form-check-label' : 'form-label',
            ],
            Html::escape($this->label) . ($this->required ? self::REQUIRED_MARKER : ''),
        );

        $help = $helpId === null
            ? ''
            : Html::tag('div', ['id' => $helpId, 'class' => 'form-text'], Html::escape($helpText));
        $feedback = $errorId === null ? '' : Html::tag(
            'div',
            ['id' => $errorId, 'class' => 'invalid-feedback celerik-server-error'],
            Html::escape($error),
        );

        $content = $this->type === 'checkbox'
            ? $control . $label . $feedback . $help
            : $label . $control . $feedback . $help;

        return Html::tag('div', ['class' => $this->type === 'checkbox' ? 'mb-3 form-check' : 'mb-3'], $content);
    }

    public static function fieldId(string $formId, string $name): string
    {
        return $formId . '-' . trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $name), '-');
    }

    public function isFile(): bool
    {
        return $this->type === 'file';
    }

    /**
     * @param array<string, string|int|float|bool|null> $attributes
     */
    private function renderFile(array $attributes, ?string $error): string
    {
        $rules = $this->uploadRules ?? UploadRules::images();
        $multiple = $rules->maxFiles > 1;

        $attributes['name'] = $multiple && !str_ends_with($this->name, '[]') ? $this->name . '[]' : $this->name;
        $attributes['placeholder'] = null;

        $upload = $this->uploadUrl === null ? null : self::json([
            'url' => $this->uploadUrl,
            'required' => $this->required,
            'maxFiles' => $rules->maxFiles,
            'maxSize' => $rules->maxSize,
            'types' => array_keys($rules->types),
        ]);

        return Html::tag('input', $attributes + [
            'type' => 'file',
            'accept' => $rules->accept(),
            'multiple' => $multiple,
            'class' => $this->controlClass('form-control', $error),
            'data-celerik-upload' => $upload,
            // FilePond replaces this input; it validates the files itself.
            'data-parsley-excluded' => $upload === null ? null : 'true',
        ]);
    }

    private function assertType(string $type, string $method): void
    {
        if ($this->type !== $type) {
            throw new InvalidArgumentException(sprintf('%s() is only available on %s fields.', $method, $type));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function json(array $data): string
    {
        return json_encode(
            array_filter($data, static fn (mixed $value): bool => $value !== null),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    private function controlClass(string $base, ?string $error): string
    {
        return $error === null ? $base : $base . ' is-invalid';
    }

    private function renderChoices(mixed $selected): string
    {
        $html = $this->placeholder === null
            ? ''
            : Html::tag('option', ['value' => ''], Html::escape($this->placeholder));

        foreach ($this->choices as $value => $label) {
            $html .= Html::tag('option', [
                'value' => (string) $value,
                'selected' => $selected !== null && (string) $selected === (string) $value,
            ], Html::escape($label));
        }

        return $html;
    }

    private static function humanize(string $name): string
    {
        return ucfirst(trim(str_replace(['_', '-', '[', ']'], ' ', $name)));
    }
}
