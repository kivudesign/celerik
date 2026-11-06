# Modal

A Bootstrap 5 dialog, labelled by its title, with footer buttons that can submit a form placed in its body.

<Demo name="modal" :min-height="340" />

<<< @/demos/modal.php

## Options

| Method | Use |
|---|---|
| `title($title)` | The heading, which also names the dialog. |
| `body($body)` | Text (escaped) or a `Stringable`: a form, another component, `Html::raw()`. |
| `button($label, $variant = 'primary', $dismiss = false, $attributes = [])` | A footer button; `['type' => 'submit', 'form' => 'contact']` submits a form in the body. |
| `cancelButton($label = 'Cancel')` | A button that closes the dialog. |
| `trigger($label, $variant = 'primary', $attributes = [])` | A button that opens it. |
| `size('sm' \| 'lg' \| 'xl' \| 'fullscreen')`, `centered()`, `scrollable()` | Layout. |
| `staticBackdrop()` | A click outside does not close it (for forms); Escape and the close button still do. |
| `open()` | Opens it when the page loads, e.g. to show a form again with its server errors. |
| `closeLabel($label)` | The accessible name of the × button. |

```php
$modal = Modal::make('newsletter')
    ->title('Subscribe')
    ->body($form)                       // Form::make('newsletter-form')->…->submit(null)
    ->cancelButton()
    ->button('Subscribe', 'primary', attributes: ['type' => 'submit', 'form' => 'newsletter-form'])
    ->staticBackdrop()
    ->open($errors !== []);
```

Bootstrap's JavaScript (`bootstrap.bundle.js`) must be on the page.

## Accessibility

Bootstrap's dialog pattern: focus moves into the dialog and stays there, Escape closes it, and focus returns to the trigger. The dialog is named by its title.
