# Symfony example

Drop-in files for a Symfony 7 web application, showing a server-side Grid (Doctrine), a Chart, a validated Form and an editable Scheduler.

## Setup

```bash
symfony new celerik-demo --webapp && cd celerik-demo
composer config repositories.celerik path ../celerik
composer require celerik/ui:@dev
```

Register the bundle in `config/bundles.php`:

```php
Celerik\Ui\Adapters\Symfony\CelerikUiBundle::class => ['all' => true],
```

Install the assets. This copies the package's `public/` folder to `public/bundles/celerikui`:

```bash
php bin/console assets:install
```

Copy the example files into the app:

| From `examples/symfony/` | To the app |
|---|---|
| `src/Controller/CelerikDemoController.php`, `CelerikCalendarController.php` | `src/Controller/` |
| `src/Entity/Product.php`, `Meeting.php` and their repositories | `src/Entity/`, `src/Repository/` |
| `templates/celerik/*.html.twig` | `templates/celerik/` |

Create the table and some data, for example with SQLite (`DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"`):

```bash
php bin/console doctrine:schema:create
php bin/console dbal:run-sql "INSERT INTO product (name, category, price) VALUES ('Desk', 'Furniture', 249.00), ('Chair', 'Furniture', 129.00), ('Lamp', 'Lighting', 39.90), ('Monitor', 'Electronics', 199.00)"
symfony serve
```

Then open `/celerik/grid`, `/celerik/chart`, `/celerik/form` and `/celerik/scheduler`.

## What it shows

- **`{{ celerik_assets(asset('bundles/celerikui')) }}`** prints the `<link>` and `<script>` tags for the bundle. Load jQuery and Bootstrap before it.
- **`{{ celerik(grid) }}`** renders any Celerik component. The output is already escaped, and the function is marked `is_safe`.
- **`Grid::respond()` + `DoctrineDataSource`**
  - Paging, sorting and search run in DQL.
  - Select scalar fields (`p.id, p.name…`) so rows serialize cleanly to JSON.
- **Form**
  - The CSRF token is checked with `isCsrfTokenValid()`.
  - The Validator component stays the source of truth; its violations are passed back with `errors()`.
- **Scheduler + `DoctrineEventSource`**
  - Only meetings overlapping the visible range are queried.
  - Changes are POSTed as JSON with the `X-CSRF-TOKEN` header, checked with `isCsrfTokenValid()`. `Event::fromArray()` validates them.
  - Dates are converted to the server time zone before saving, because Doctrine drops UTC offsets.
  - If the server refuses a change (validation, CSRF, 404), the calendar rolls it back.
