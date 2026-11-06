# Celerik UI

[![CI](https://github.com/kivudesign/celerik/actions/workflows/ci.yml/badge.svg)](https://github.com/kivudesign/celerik/actions/workflows/ci.yml)
[![Documentation](https://img.shields.io/badge/docs-kivudesign.github.io%2Fcelerik-0a58ca)](https://kivudesign.github.io/celerik/)

An open-source, Bootstrap-based UI component suite for PHP, inspired by Telerik UI. It works with plain PHP and has optional adapters for **Laravel 11+** and **Symfony 7**.

Components are written in PHP and render accessible, escaped HTML. A small JS bundle then enhances them with proven open-source libraries: DataTables, Chart.js, Parsley.js, Inputmask, FullCalendar, Quill, FilePond, Notyf and elFinder. Each library (and its stylesheet) is loaded only on pages that use it.

> **Status: Phase 3 complete.** Grid, Chart, Form (with uploads and a rich text editor), Scheduler, Modal, Tabs, Notification, File Manager and ThemeBuilder are available, accessible to WCAG 2.2 AA and documented. See the [roadmap](ROADMAP.md).

**Documentation: <https://kivudesign.github.io/celerik/>**, with getting started guides for plain PHP, Laravel and Symfony, every component with a live demo, the theming and security guides, and the API reference.

## Quick look

```php
use Celerik\Ui\Components\Grid;
use Celerik\Ui\Adapters\Laravel\EloquentDataSource;

$grid = Grid::make('users')
    ->caption('Registered users')
    ->url('/users/data')                // server-side paging, sorting, search
    ->column('name')
    ->column('email', 'E-mail')
    ->export('copy', 'csv', 'print');

// In the /users/data controller, using the same Grid definition:
return response()->json($grid->respond(new EloquentDataSource(User::query()), $request->query()));
```

```blade
<x-celerik-assets />
<x-celerik :component="$grid" />
```

## Installation

```bash
composer require celerik/ui
```

Load Bootstrap 5.3 (CSS, and `bootstrap.bundle.js` for Modal and Tabs) and jQuery 3.7, then the Celerik bundle:

| Setup | Publish assets | Assets tag | Render |
|---|---|---|---|
| Laravel | `php artisan vendor:publish --tag=celerik-assets` | `<x-celerik-assets />` | `<x-celerik :component="$grid" />` |
| Symfony | add `CelerikUiBundle` to `config/bundles.php`, then `php bin/console assets:install` | `{{ celerik_assets() }}` | `{{ celerik(grid) }}` |
| Plain PHP | serve the package's `public/` folder | `<?= Assets::tags('/assets') ?>` | `<?= $grid ?>` |

Releases include the built bundle (`public/`), so no Node.js is needed to install. To skip publishing the assets, load them from the jsDelivr CDN instead: `Assets::tags(Assets::cdnUrl())` (or `<x-celerik-assets :base-url="Celerik\Ui\Assets::cdnUrl()" />`). Projects with a JavaScript bundler can also install the bundle from npm: `npm install @celerikui/ui`, then `import '@celerikui/ui'` and `import '@celerikui/ui/celerik.css'`.

## Components

| Component | PHP class | Library | Highlights |
|---|---|---|---|
| Grid | `Components\Grid` | DataTables 2 | Client- or server-side, export, caption, column whitelisting |
| Chart | `Components\Chart` | Chart.js 4 | Embedded or remote data, `fromRows()`, hidden data table as text alternative |
| Form | `Components\Form` | Parsley.js, Inputmask | HTML5 rules, masks, CSRF, method spoofing, server errors and old input |
| File upload | `Field::file()` + `Upload\UploadHandler` | FilePond 4 | Async or classic uploads; real MIME detection, extension whitelist, random names; falls back to a plain file input |
| Rich text editor | `Field::editor()` + `Support\EditorHtml` | Quill 2 | Toolbar presets, accessible labelling, server-side sanitizing (`symfony/html-sanitizer`) |
| Modal | `Components\Modal` | Bootstrap 5 | Labelled dialog, footer buttons that submit a form inside, opens on load after server errors |
| Tabs | `Components\Tabs` | Bootstrap 5 | WAI-ARIA tabs with arrow keys, tabs/pills/underline styles, selected tab kept in the URL hash |
| Notification | `Components\Notification` | Notyf 3 | Toasts from flash messages (`fromFlash()`) or `Celerik.notify()`; messages are always text; errors stay until dismissed |
| Scheduler | `Components\Scheduler` | FullCalendar 6 (MIT plugins) | Month, week, day and list views; create, move, resize and delete through your endpoints (also from the keyboard), rolled back if the server refuses |
| Theme builder | `Components\ThemeBuilder` + `Theme\Theme` | – | Edit theme tokens with a live preview of your components and a contrast report; export CSS or JSON; see [THEMING.md](THEMING.md) |
| File manager | `Components\FileManager` + `FileManager\FileManagerConnector` | elFinder 2.1 | Locked-down local volume, mandatory authorization, content-checked uploads, elFinder CSRF plus the framework's; see [SECURITY.md](SECURITY.md#file-manager) |

Content passed to Modal and Tabs follows one rule: a string is escaped text, and a `Stringable` (another component, or `Html::raw()` for markup you trust) is inserted as HTML.

The full Telerik → open-source mapping is in [Components.md](Components.md).

## Themes

Three looks, all on Bootstrap 5.3, each with light and dark mode (`<html data-bs-theme="dark">`):

| Theme | Look | Use it |
|---|---|---|
| `bootstrap` (default) | Plain Bootstrap | `<x-celerik-assets />`, `{{ celerik_assets() }}`, `Assets::tags($url)` |
| `material` | Material 3 palette, pill buttons, elevated surfaces, flat tables, rounded dialogs | `<x-celerik-assets theme="material" />`, `{{ celerik_assets(theme: 'material') }}`, `Assets::tags($url, theme: 'material')` |
| `tailwind` | Tailwind-inspired: slate and indigo, ring focus, uppercase table headers (does **not** load Tailwind CSS) | `theme="tailwind"` |

A theme is a small extra stylesheet (about 1 KB gzipped) loaded after `celerik.css`. Pair the Material theme with `Form::floatingLabels()` for floating labels. Every theme and mode is checked for WCAG 2.1 AA contrast, both automatically on its palette and in a browser with axe-core.

To make your own, start from a look and change its design tokens (ten colors per mode, radius, font, shadow):

- the **ThemeBuilder** component edits them with a live preview of your components and a WCAG contrast report, and exports the stylesheet or the theme JSON;
- `Theme::preset('tailwind')->color('light', 'primary', '#0b5394')->toCss()` does the same in PHP;
- `vendor/bin/celerik-theme build theme.json public/css/acme.css` builds it in CI, and refuses a theme that fails a contrast check.

Load the result with `customTheme` (`<x-celerik-assets theme="tailwind" custom-theme="/css/acme.css" />`). See [THEMING.md](THEMING.md); for changes tokens cannot make, override the CSS custom properties or the SCSS variables.

## Examples

- [`examples/plain`](examples/plain): no framework, with a theme switcher (`?theme=material&mode=dark`). Run `php -S localhost:8000 examples/plain/index.php`.
- [`examples/laravel`](examples/laravel): Eloquent grid, chart, validated form, scheduler, an article form with rich text and uploads, a tabs/modal/notifications page, and a file manager.
- [`examples/symfony`](examples/symfony): Doctrine grid, chart, validated form, scheduler, an article form with rich text and uploads, a tabs/modal/notifications page, and a file manager.

## Development

```bash
composer install && npm install
npm run build        # public/: the bundle is built, not committed (releases add it to their tag)
composer test        # PHPUnit
npm test             # Jest + axe-core (renders PHP fixtures first)
npm run test:a11y    # Playwright + axe-core in a real browser, every theme (after npm run build)
composer lint        # PSR-12
npm run lint         # ESLint
```

The documentation site is built from `site/` (`npm run docs:dev`, `npm run docs:build`); its demos are rendered by PHP and its API reference is generated from the code.

See [CONTRIBUTING.md](CONTRIBUTING.md) and [TESTING.md](TESTING.md).

## Content Security Policy

Components emit no inline scripts, so a strict policy works. Pass a nonce to the assets tag (`<x-celerik-assets nonce="..." />`, `celerik_assets(null, nonce)` or `Assets::tags($url, $nonce)`). It is also printed as `<meta name="csp-nonce">`, which FullCalendar uses for the styles it injects.

Tested policy:

```text
default-src 'self'; script-src 'self' 'nonce-…'; style-src 'self' 'nonce-…';
img-src 'self' data:; font-src 'self' data:; connect-src 'self'
```

`font-src data:` is needed for the Scheduler's toolbar icons. Add your CDN to `script-src` and `style-src` if Bootstrap and jQuery come from one.

The File Manager also needs `style-src-attr 'unsafe-inline'`: elFinder builds its interface with inline `style` attributes. That directive allows style attributes only (they cannot run code); scripts and `<style>` elements still require the nonce.

## Accessibility

Components target WCAG 2.2 AA in every theme, light and dark, and respect reduced motion and Windows High Contrast. Every pull request runs axe-core in a real browser, plus keyboard, forced-colors and target-size tests. See [ACCESSIBILITY.md](ACCESSIBILITY.md) for the status of each component, the known limits of the bundled libraries and the manual test plan.

## Documentation Pack

| Document | Purpose |
|---|---|
| [Documentation site](https://kivudesign.github.io/celerik/) | Guides, components with live demos, API reference (source in `site/`) |
| [ROADMAP.md](ROADMAP.md) | Phased delivery plan |
| [Components.md](Components.md) | Telerik → open-source component mapping, licenses, phases |
| [CONTRIBUTING.md](CONTRIBUTING.md) | How to contribute, project rules, adding a component |
| [TESTING.md](TESTING.md) | Test suites and how to run them |
| [ACCESSIBILITY.md](ACCESSIBILITY.md) | Conformance target, per-component status, manual test plan |
| [THEMING.md](THEMING.md) | Theme tokens, ThemeBuilder, `Theme` in PHP, the `celerik-theme` command |
| [SECURITY.md](SECURITY.md) | Reporting vulnerabilities |
| [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md) | Licenses of bundled libraries |
| [AI.md](AI.md) | Notes for AI-assisted development |
| [structure.md](structure.md) | Repository layout |

## License

- Core: [MIT](LICENSE.md).
- Bundled libraries keep their own licenses: see [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).
- Optional premium extensions may use commercial licensing.

Telerik is a trademark of Progress Software Corporation. Celerik UI is an independent project, not affiliated with or endorsed by Progress.
