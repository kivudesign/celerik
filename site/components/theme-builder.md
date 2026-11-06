# Theme builder

Edit a theme's design tokens with a live preview of your own components and a WCAG contrast report, then export the stylesheet or the theme JSON. The demo below runs entirely in your browser: try Download CSS.

<Demo name="theme-builder" :min-height="900" />

<<< @/demos/theme-builder.php

## Options

| Method | Use |
|---|---|
| `theme(Theme $theme)` | The theme to start from (default: the bootstrap preset). |
| `preview(...$components)` | Components (or trusted HTML) shown in the preview. |
| `saveUrl($url)`, `csrf($token, $field = '_token')` | Adds a Save button posting the theme. |
| `assetsUrl($url)` | Where the bundle is, to switch the base look (default: next to the page's `celerik.css`). |
| `labels([...])` | Translate the builder (keys of `ThemeBuilder::LABELS`, or token names). |

## Saving

```php
// POST /admin/theme (check authorization and the CSRF token first)
$theme = ThemeBuilder::fromRequest($_POST, file_get_contents('php://input'));
file_put_contents('theme.json', $theme->toJson());
file_put_contents('public/css/theme.css', $theme->toCss());
```

`fromRequest()` reads the JSON sent by the Save button, or the form fields posted without JavaScript, and throws `InvalidArgumentException` for an invalid theme (answer 422).

The builder is a development tool: put it behind authorization or a local-only route. Tokens, `Theme` in PHP and the `celerik-theme` command are described in [THEMING.md](https://github.com/kivudesign/celerik/blob/main/THEMING.md).

## Accessibility

Every color has a labelled hex field (the color pickers are skipped in the Tab order), the report says Pass or Fail in text and announces only its summary, and invalid values are reported on their field. Without JavaScript it is a form with a contrast report computed in PHP.
