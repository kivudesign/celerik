# Form

Bootstrap forms with client-side validation ([Parsley.js](https://parsleyjs.org/)), input masks ([Inputmask](https://github.com/RobinHerbots/Inputmask)), server-side errors and old input. Submit the demo empty to see the error summary.

<Demo name="form" :min-height="560" />

<<< @/demos/form.php

## The form

| Method | Use |
|---|---|
| `action($url)`, `method('POST')` | `PUT`, `PATCH` and `DELETE` are sent as `POST` with a `_method` field (the Laravel and Symfony convention). |
| `csrf($token, $field = '_token')` | A hidden CSRF field. |
| `add(Field ...$fields)` | The fields, in order. |
| `errors([...])` | Server-side errors: `field => message` or `field => [messages]`; `address.city` matches `address[city]`. |
| `values($_POST)` | Old input after a failed submit (passwords are never refilled). |
| `errorSummary($enabled = true, $title = 'There is a problem')` | Lists every error at the top with a link to each field, and moves focus to it after a failed submit. |
| `floatingLabels()` | Bootstrap floating labels (the Material look). |
| `submit($label)` | The submit button's label; `null` renders none (e.g. a form whose button is in a modal footer). |

Client-side validation is a convenience: always validate again on the server and pass the messages to `errors()`.

## Fields

```php
Field::text('name')->required()->maxLength(80);
Field::email('email', 'E-mail')->required()->help('We never share your e-mail.');
Field::password('password_confirmation', 'Confirm password')->required()->equalTo('password');
Field::tel('phone')->mask('(999) 999-9999');
Field::number('age')->min(18)->max(120);
Field::select('country', ['fr' => 'France', 'ca' => 'Canada'])->placeholder('Choose...');
Field::checkbox('terms', 'I accept the terms')->required();
Field::textarea('message')->minLength(10);
Field::date('birthday');
Field::hidden('id', 42);
```

Rich text and files have their own pages: [Rich text editor](./editor) and [File upload](./upload).

### Choices, switches, sliders and colors

<Demo name="form-choices" :min-height="520" />

<<< @/demos/form-choices.php

| Field | Renders | Submits |
|---|---|---|
| `Field::radio($name, $choices)` | Radio buttons in a `<fieldset>`, the label as its `<legend>`. `required()` requires a choice. | The chosen value. |
| `Field::checkboxes($name, $choices)` | Checkboxes in a `<fieldset>`. `required()` requires at least one; `value()` takes an array. | `name[]`: the checked values, as an array. |
| `Field::switch($name)` | A checkbox shown as a toggle, announced as a switch. | `1` when on, nothing when off. |
| `Field::range($name)` | A slider; its value is shown after the label. `min()`, `max()` and `step()` default to 0, 100 and 1. | The number. |
| `Field::color($name)` | A color picker. | A hex color, e.g. `#0d6efd`. |

`inline()` puts the choices of a radio or checkbox group on one line. `step()` also works on number, date and time fields.

| Rule or option | Use |
|---|---|
| `required()`, `minLength()`, `maxLength()`, `min()`, `max()` | HTML5 rules, also checked by Parsley. |
| `pattern('[A-Z]{2}[0-9]{4}')` | A regular expression, anchored, without delimiters. |
| `equalTo('password')` | Same value as another field. |
| `mask('99/99/9999')` | An input mask. |
| `message('...')` | A custom error message for every rule of the field. |
| `placeholder()`, `help()`, `value()`, `attribute($name, $value)` | Placeholder, help text, value, any attribute. |

## Accessibility

- Every field has a label; help and error texts are linked with `aria-describedby`, and invalid fields get `aria-invalid`.
- Radio and checkbox groups are fieldsets whose legend names the group; every input of an invalid group gets `aria-invalid`.
- A failed submit focuses the first invalid field, or the error summary, which is announced once instead of one message per field.
- Required fields are marked with an asterisk hidden from screen readers, plus `required`.
