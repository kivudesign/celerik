# Rich text editor

A [Quill 2](https://quilljs.com/) editor in a form field. It submits HTML, which you must sanitize on the server.

<Demo name="editor" :min-height="380" />

<<< @/demos/editor.php

## Options

| Method | Use |
|---|---|
| `Field::editor($name, $label = null)` | The field. Without JavaScript it is a textarea. |
| `toolbar('basic' \| 'standard' \| 'full')` | Toolbar presets (default `standard`), or a Quill toolbar array. Presets limit pasted content to the same formats. |
| `required()`, `help()`, `value($html)` | As for other fields. |

## Sanitize on the server

The browser can send anything: sanitize the HTML before you store it or show it.

```php
use Celerik\Ui\Support\EditorHtml;

$html = EditorHtml::sanitize((string) $_POST['body']);
```

`EditorHtml::sanitize()` (on `symfony/html-sanitizer`) keeps what the toolbars produce: paragraphs, headings (h2 to h4), bold, italic, underline, strike, lists, quotes, code, and http(s) and mailto links (forced to `rel="noopener noreferrer"`). Scripts, event handlers, `javascript:` links, iframes and styles are removed. Pass your own `HtmlSanitizerConfig` as the second argument to change that.

## Accessibility

- The editing area is a labelled `textbox` with `aria-multiline`, and carries `aria-required`, `aria-invalid` and the field's help text.
- Tab and Shift+Tab move in and out of the editor, as in a textarea; the toolbar buttons are in the Tab order.
