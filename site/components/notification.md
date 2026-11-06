# Notification

Toasts on [Notyf 3](https://github.com/caroso1222/notyf), from flash messages or from JavaScript.

<Demo name="notification" :min-height="240" />

<<< @/demos/notification.php

## From flash messages

```php
// Laravel: session()->flash('success', 'Saved.');
Notification::make('flash')->fromFlash(session()->only(['success', 'error', 'info', 'warning']));

// Symfony: $this->addFlash('success', 'Saved.');
Notification::make('flash')->fromFlash($request->getSession()->getFlashBag()->all());
```

`fromFlash()` takes `key => message` or `key => [messages]`, and maps common keys (`status` → success, `danger` → error, `notice` → info).

## From JavaScript

```js
const response = await fetch('/profile', { method: 'POST', body });
Celerik.notify(response.ok ? 'success' : 'error', response.ok ? 'Saved.' : 'Could not save.');
```

## Options

| Method | Use |
|---|---|
| `success()`, `error()`, `info()`, `warning()`, `add($type, $message)` | Messages, always shown as text. |
| `duration($ms)` | How long success, info and warning toasts stay (default 5000). |
| `errorDuration($ms)` | For errors; 0 (default) keeps them until dismissed. |
| `position('right', 'top')` | x `left`, `center` or `right`; y `top` or `bottom`. |
| `dismissible()`, `dismissLabel($label)` | Dismiss buttons, and their accessible name. |

## Accessibility

- Messages are announced in a polite live region, as plain text.
- Toasts pause while the pointer or keyboard focus is on them (WCAG 2.2.1); errors stay until dismissed.
- Dismiss buttons are named and work from the keyboard; animations are cut when the user asks for reduced motion.
