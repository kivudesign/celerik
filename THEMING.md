# Theming

A Celerik theme is a **base look** (`bootstrap`, `material` or `tailwind`) plus **design tokens**: ten colors for light mode, ten for dark mode, a corner radius, a font and a container shadow. From the tokens Celerik writes a small stylesheet, loaded after the base look's stylesheet. Everything else (spacing, the shape of buttons and dialogs, table style) comes from the base look.

There are three ways to make one, and they all produce the same file:

| Tool | For | Output |
|---|---|---|
| [ThemeBuilder](#themebuilder) | Designing a theme with a live preview of real components and a contrast report | `theme.json`, `theme.css`, a PHP snippet |
| [`Theme` in PHP](#theme-in-php) | Themes in code or configuration, per tenant or per user | `Theme::toCss()` |
| [`celerik-theme` command](#command-line) | Keeping `theme.json` in git and building the CSS in CI | `theme.css` |

## Tokens

Defined in [`theme/theme.schema.json`](theme/theme.schema.json). The shipped looks are presets in [`theme/presets/`](theme/presets/).

| Token | Used for | Contrast needed |
|---|---|---|
| `primary` | Buttons, links, active tabs, the current page, calendar events | 4.5:1 on `bodyBg` |
| `primaryHover` | Hovered and pressed buttons, hovered links | 4.5:1 on `bodyBg` |
| `onPrimary` | Text and icons on `primary` and `primaryHover` | 4.5:1 on both |
| `bodyBg` | Page background | – |
| `bodyColor` | Body text | 4.5:1 on `bodyBg` and `surface` |
| `muted` | Help texts, captions, placeholders | 4.5:1 on `bodyBg` and `surface` |
| `surface` | Grid header, upload area, toolbars | – |
| `border` | Dividers and decorative borders | – |
| `inputBorder` | Borders of form fields | 3:1 on `bodyBg` (WCAG 1.4.11) |
| `danger` | Error messages and invalid fields | 4.5:1 on `bodyBg` |

`radius` is in rem (0 to 2), `fontFamily` is a font stack of family names (for example `Inter, system-ui, sans-serif`; it does not load fonts), and `shadow` is `none`, `subtle`, `elevated` or `outlined`. Leave them out to keep the base look's values.

The contrast pairs are listed once, in [`theme/contrast.json`](theme/contrast.json), and checked by PHP, the ThemeBuilder and the tests. Thresholds are not rounded: 4.497:1 fails 4.5:1.

## Loading a theme

```php
Assets::tags('/vendor/celerik', theme: 'tailwind', customTheme: '/css/acme.css');
```

```blade
<x-celerik-assets theme="tailwind" custom-theme="/css/acme.css" />
```

```twig
{{ celerik_assets(theme: 'tailwind', customTheme: '/css/acme.css') }}
```

`theme` must be the theme's base. The custom stylesheet is loaded last.

## ThemeBuilder

```php
use Celerik\Ui\Components\ThemeBuilder;
use Celerik\Ui\Theme\Theme;

echo ThemeBuilder::make('builder')
    ->theme(Theme::preset('material'))        // or your saved theme
    ->preview($grid, $form, $chart, $tabs)     // your own components
    ->saveUrl('/admin/theme')                  // optional
    ->csrf($token);
```

- Every token has a labelled hex field and a color picker. The preview uses the stylesheet the theme will produce, applied to the preview area only, so the builder's own controls stay readable whatever the colors. The base look and the light or dark preview can be switched.
- The contrast report updates as you type. Without JavaScript it is computed in PHP.
- **Download CSS**, **Download JSON**, **Copy PHP** (a `Theme::fromJson()` snippet), **Import JSON**, **Undo** and **Reset**.
- **Save** posts `{"theme": {...}}` as JSON with the CSRF token in the `X-CSRF-TOKEN` header. Without JavaScript the form posts its fields. Read both the same way:

```php
// Check authorization and the CSRF token first.
try {
    $theme = ThemeBuilder::fromRequest($_POST, file_get_contents('php://input'));
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    exit($e->getMessage());
}
file_put_contents(__DIR__ . '/theme.json', $theme->toJson());
file_put_contents(__DIR__ . '/public/css/theme.css', $theme->toCss());
```

The ThemeBuilder is a development tool: put it behind authorization or a local-only route. The examples show it in plain PHP, Laravel (only with `APP_ENV=local`) and Symfony (dev environment only), saving the theme and loading it on every page. Texts can be translated with `labels([...])`.

## Theme in PHP

```php
use Celerik\Ui\Theme\Contrast;
use Celerik\Ui\Theme\Theme;

$theme = Theme::preset('tailwind')
    ->name('Acme')
    ->color('light', 'primary', '#3b82f6')
    ->color('light', 'primaryHover', '#2563eb')
    ->radius(0.25)
    ->fontFamily('Inter, system-ui, sans-serif');

foreach (Contrast::failures($theme) as $failure) {
    echo $failure, "\n";
}
// FAIL light: Links and outline buttons on #ffffff = 3.67:1 (needs 4.5:1, WCAG 1.4.3)
// FAIL light: Primary button label on #3b82f6 = 3.67:1 (needs 4.5:1, WCAG 1.4.3)

$css = $theme->toCss();
$json = $theme->toJson();          // Theme::fromJson($json) reads it back
```

Every value is validated against a strict format (hex colors, plain font names, numbers), so a theme from user input, for example a tenant's brand colors, cannot inject CSS into the stylesheet. Cache the generated CSS: it only changes when the theme does.

## Command line

```bash
vendor/bin/celerik-theme preset tailwind > theme.json     # start from a preset
vendor/bin/celerik-theme check theme.json                 # contrast report, exit 1 on a failure
vendor/bin/celerik-theme build theme.json public/css/acme.css
```

`build` refuses a theme that fails a contrast pair unless you pass `--allow-contrast-failures`. Exit codes: 0 done, 1 a contrast check failed, 2 invalid usage or theme.

## How far a theme goes

A theme changes colors, the corner radius, the font and container shadows, in light and dark mode, on top of a base look. For anything else:

- **CSS custom properties.** Override `--bs-*` and `--celerik-*` properties in your own stylesheet, loaded after the theme. No build step.
- **SCSS.** The variables in `resources/scss/_variables.scss` are `!default`. Compile your own entry file (see `resources/scss/themes/`) for changes custom properties cannot make, such as Bootstrap's breakpoints or spacing scale.

## Tests

- The presets match the compiled base looks property by property (`tests/js/presets.test.js`).
- `Theme::toCss()` and the ThemeBuilder's JavaScript write the same text, byte for byte (`tests/js/theme-css.test.js`).
- In a browser, a custom theme restyles all three bases in light and dark mode with no axe-core violations, and a theme exported from the ThemeBuilder passes the same check (`tests/browser/custom-theme.spec.mjs`, `theme-builder-a11y.spec.mjs`).
