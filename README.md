# Celerik UI

An open-source, Bootstrap-based UI component suite for PHP, inspired by Telerik UI. It works with plain PHP and has optional adapters for **Laravel 11+** and **Symfony 7**.

Components are written in PHP and render accessible, escaped HTML. A small JS bundle then enhances them with proven open-source libraries: DataTables, Chart.js, Parsley.js and Inputmask.

> **Status: Phase 1 (MVP).** Grid, Chart and Form are available. See the [roadmap](ROADMAP.md).

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

## Components (Phase 1)

| Component | PHP class | Library | Highlights |
|---|---|---|---|
| Grid | `Components\Grid` | DataTables 2 | Client- or server-side, export, caption, column whitelisting |
| Chart | `Components\Chart` | Chart.js 4 | Embedded or remote data, `fromRows()`, hidden data table as text alternative |
| Form | `Components\Form` | Parsley.js, Inputmask | HTML5 rules, masks, CSRF, method spoofing, server errors and old input |

The full Telerik → open-source mapping is in [Components.md](Components.md).

## Examples

- [`examples/plain`](examples/plain): no framework. Run `php -S localhost:8000 examples/plain/index.php`.
- [`examples/laravel`](examples/laravel): Eloquent grid, chart and validated form.
- [`examples/symfony`](examples/symfony): Doctrine grid, chart and validated form.

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

## Documentation Pack

| Document | Purpose |
|---|---|
| [doc/PRD.md](doc/PRD.md) | Product requirements: scope, goals, constraints |
| [doc/Architecture.md](doc/Architecture.md) | Layers, component and data contracts, data flow, assets, plugins |
| [doc/Rules.md](doc/Rules.md) | Coding, accessibility, performance, licensing and security rules |
| [doc/Phases.md](doc/Phases.md) | Phased delivery plan |
| [doc/Design.md](doc/Design.md) | UI principles and theming |
| [doc/Memory.md](doc/Memory.md) | Product memory: presets and theme cache |
| [AI.md](AI.md) | Notes for AI-assisted development |
| [Components.md](Components.md) | Telerik → open-source component mapping |
| [doc/guides/](doc/guides) | Usage guide, API reference, theming guide |
| [structure.md](structure.md) | Repository layout |

## License

- Core: [MIT](LICENSE.md).
- Bundled libraries keep their own licenses: see [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).
- Optional premium extensions may use commercial licensing.

Telerik is a trademark of Progress Software Corporation. Celerik UI is an independent project, not affiliated with or endorsed by Progress.
