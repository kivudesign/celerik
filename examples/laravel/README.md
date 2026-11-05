# Laravel example

Drop-in files for a fresh Laravel 11+ application (verified on Laravel 13), showing a server-side Grid (Eloquent), a Chart, a validated Form, an editable Scheduler, an article form with a rich text editor and uploads, and a page with Tabs, a Modal and Notifications.

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
| `app/Http/Controllers/CelerikDemoController.php`, `CelerikCalendarController.php`, `CelerikArticleController.php`, `CelerikUiController.php` | `app/Http/Controllers/` |
| `app/Models/Meeting.php` | `app/Models/` |
| `database/migrations/*_create_meetings_table.php` | `database/migrations/` (then run `php artisan migrate`) |
| `routes/celerik.php` | `routes/` (then add `require __DIR__ . '/celerik.php';` to `routes/web.php`) |
| `resources/views/celerik/*.blade.php` | `resources/views/celerik/` |

Seed some users and run the app:

```bash
php artisan tinker --execute="App\Models\User::factory(120)->create()"
php artisan serve
```

The article form also needs the sanitizer: `composer require symfony/html-sanitizer`.

Then open `/celerik/grid`, `/celerik/chart`, `/celerik/form`, `/celerik/scheduler`, `/celerik/article` and `/celerik/ui`.

> **Windows:** `php artisan serve` starts PHP without the `TEMP`/`TMP` environment variables, so every upload fails with "unable to create a temporary file". Use `cd public && php -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php` instead, or set `upload_tmp_dir` in `php.ini`.

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
- **Article form (editor + uploads)**
  - FilePond uploads each file to `/celerik/article/upload/{field}` with the CSRF header; `UploadHandler` validates it and answers an id.
  - On submit, the ids are claimed into `storage/app/private`, outside `public/`. Without JavaScript the real files are stored instead.
  - The editor HTML is cleaned with `EditorHtml::sanitize()` before it is stored or displayed.
- **Tabs, Modal and Notification** (`/celerik/ui`)
  - The newsletter form sits in a Modal. When `$request->validate()` fails, `open($errors !== [])` reopens the modal with the errors.
  - On success the controller redirects `with('success', ...)`, and `Notification::make()->fromFlash(session()->only([...]))` shows it as a toast.
