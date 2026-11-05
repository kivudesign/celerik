# Celerik UI

An open-source, Bootstrap-based UI component suite for PHP, inspired by Telerik UI. It works with plain PHP and has optional adapters for **Laravel 11+** and **Symfony 7**.

Components are written in PHP and render accessible, escaped HTML. A small JS bundle then enhances them with proven open-source libraries: DataTables, Chart.js, Parsley.js, Inputmask and FullCalendar. Each library is loaded only on pages that use it.

> **Status: Phase 2 in progress.** Grid, Chart, Form and Scheduler are available. See the [roadmap](ROADMAP.md).

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

Load Bootstrap 5.3 and jQuery 3.7, then the Celerik bundle:

| Setup | Publish assets | Assets tag | Render |
|---|---|---|---|
| Laravel | `php artisan vendor:publish --tag=celerik-assets` | `<x-celerik-assets />` | `<x-celerik :component="$grid" />` |
| Symfony | add `CelerikUiBundle` to `config/bundles.php`, then `php bin/console assets:install` | `{{ celerik_assets() }}` | `{{ celerik(grid) }}` |
| Plain PHP | serve the package's `public/` folder | `<?= Assets::tags('/assets') ?>` | `<?= $grid ?>` |

## Components

| Component | PHP class | Library | Highlights |
|---|---|---|---|
| Grid | `Components\Grid` | DataTables 2 | Client- or server-side, export, caption, column whitelisting |
| Chart | `Components\Chart` | Chart.js 4 | Embedded or remote data, `fromRows()`, hidden data table as text alternative |
| Form | `Components\Form` | Parsley.js, Inputmask | HTML5 rules, masks, CSRF, method spoofing, server errors and old input |
| Scheduler | `Components\Scheduler` | FullCalendar 6 (MIT plugins) | Month, week, day and list views; create, move, resize and delete through your endpoints, rolled back if the server refuses |

The full Telerik → open-source mapping is in [Components.md](Components.md).

## Examples

- [`examples/plain`](examples/plain): no framework. Run `php -S localhost:8000 examples/plain/index.php`.
- [`examples/laravel`](examples/laravel): Eloquent grid, chart, validated form and scheduler.
- [`examples/symfony`](examples/symfony): Doctrine grid, chart, validated form and scheduler.

## Development

```bash
composer install && npm install
npm run build        # public/celerik.js + public/celerik.css
composer test        # PHPUnit
npm test             # Jest + axe-core (renders PHP fixtures first)
composer lint        # PSR-12
npm run lint         # ESLint
```

See [CONTRIBUTING.md](CONTRIBUTING.md) and [TESTING.md](TESTING.md).

## Content Security Policy

Components emit no inline scripts, so a strict policy works. Pass a nonce to the assets tag (`<x-celerik-assets nonce="..." />`, `celerik_assets(null, nonce)` or `Assets::tags($url, $nonce)`). It is also printed as `<meta name="csp-nonce">`, which FullCalendar uses for the styles it injects.

Tested policy:

```text
default-src 'self'; script-src 'self' 'nonce-…'; style-src 'self' 'nonce-…';
img-src 'self' data:; font-src 'self' data:; connect-src 'self'
```

`font-src data:` is needed for the Scheduler's toolbar icons. Add your CDN to `script-src` and `style-src` if Bootstrap and jQuery come from one.

## Documentation Pack

| Document | Purpose |
|---|---|
| [ROADMAP.md](ROADMAP.md) | Phased delivery plan |
| [Components.md](Components.md) | Telerik → open-source component mapping, licenses, phases |
| [CONTRIBUTING.md](CONTRIBUTING.md) | How to contribute, project rules, adding a component |
| [TESTING.md](TESTING.md) | Test suites and how to run them |
| [SECURITY.md](SECURITY.md) | Reporting vulnerabilities |
| [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md) | Licenses of bundled libraries |
| [AI.md](AI.md) | Notes for AI-assisted development |
| [structure.md](structure.md) | Repository layout |

## License

- Core: [MIT](LICENSE.md).
- Bundled libraries keep their own licenses: see [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).
- Optional premium extensions may use commercial licensing.

Telerik is a trademark of Progress Software Corporation. Celerik UI is an independent project, not affiliated with or endorsed by Progress.
