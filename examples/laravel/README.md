# Laravel example

Drop-in files for a fresh Laravel 11+ application (verified on Laravel 13), showing a server-side Grid (Eloquent), a Chart, a validated Form and an editable Scheduler.

## Setup

```bash
laravel new celerik-demo && cd celerik-demo      # SQLite by default
composer config repositories.celerik path ../celerik
composer require celerik/ui:@dev
php artisan vendor:publish --tag=celerik-assets   # copies public/ to public/vendor/celerik
```

The service provider is auto-discovered, so no `config/app.php` change is needed.

Copy the example files into the app:

| From `examples/laravel/` | To the app |
|---|---|
| `app/Http/Controllers/CelerikDemoController.php`, `CelerikCalendarController.php` | `app/Http/Controllers/` |
| `app/Models/Meeting.php` | `app/Models/` |
| `database/migrations/*_create_meetings_table.php` | `database/migrations/` (then run `php artisan migrate`) |
| `routes/celerik.php` | `routes/` (then add `require __DIR__ . '/celerik.php';` to `routes/web.php`) |
| `resources/views/celerik/*.blade.php` | `resources/views/celerik/` |

Seed some users and run the app:

```bash
php artisan tinker --execute="App\Models\User::factory(120)->create()"
php artisan serve
```

Then open `/celerik/grid`, `/celerik/chart`, `/celerik/form` and `/celerik/scheduler`.

## What it shows

- **`<x-celerik-assets />`** prints the `<link>` and `<script>` tags for the bundle. Load jQuery and Bootstrap before it.
- **`<x-celerik :component="$grid" />`** renders any Celerik component.
- **`Grid::respond()` + `EloquentDataSource`**
  - Paging, sorting and search run in SQL.
  - Only declared columns are returned, and `$hidden` attributes (such as `password`) are never exposed.
- **Form**
  - Client-side validation uses Parsley.
  - Laravel's `$request->validate()` stays the source of truth. Its errors and old input are passed back with `errors()` and `values()`.
- **Scheduler + `EloquentEventSource`**
  - Only meetings overlapping the visible range are queried.
  - Changes are POSTed as JSON with the `X-CSRF-TOKEN` header, which Laravel's CSRF middleware accepts natively. `Event::fromArray()` validates them.
  - Dates are converted to the app time zone before saving, because Eloquent drops UTC offsets.
  - If the server refuses a change (validation, CSRF, 404), the calendar rolls it back.
