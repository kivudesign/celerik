# Getting started

Celerik UI is a set of PHP components that render accessible Bootstrap 5 markup. A small JavaScript bundle then enhances them with proven open-source libraries (DataTables, Chart.js, Parsley.js, FullCalendar, Quill, FilePond, Notyf, elFinder), each loaded only on the pages that use it.

You build a component in PHP, print it in your template, and, for server-side data, answer its requests with the same definition.

## Requirements

- PHP 8.2 or later, with `ext-fileinfo` for uploads
- Bootstrap 5.3 (CSS, and `bootstrap.bundle.js` for Modal and Tabs) and jQuery 3.7 on the page
- Optional: Laravel 11+ or Symfony 7 for the adapters

## Install

```bash
composer require celerik/ui
```

Then make the bundle (the package's `public/` folder) available to the browser:

::: code-group

```bash [Laravel]
php artisan vendor:publish --tag=celerik-assets   # copies it to public/vendor/celerik
```

```bash [Symfony]
# Register Celerik\Ui\Adapters\Symfony\CelerikUiBundle in config/bundles.php, then:
php bin/console assets:install                    # copies it to public/bundles/celerikui
```

```bash [Plain PHP]
cp -r vendor/celerik/ui/public public/vendor/celerik
```

:::

Publish the assets again after every Celerik update.

## Load the assets

Load Bootstrap and jQuery first, then the Celerik tags in the `<head>`:

::: code-group

```blade [Blade]
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<x-celerik-assets />
```

```twig [Twig]
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
{{ celerik_assets() }}
```

```php [Plain PHP]
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<?= Celerik\Ui\Assets::tags('/vendor/celerik') ?>
```

:::

The tags are a stylesheet and one ES module (`celerik.js`). Module scripts are deferred, so they run after jQuery and Bootstrap.

## Your first component

<Demo name="grid" :min-height="420" />

<<< @/demos/grid.php

Print it in your template:

::: code-group

```blade [Blade]
<x-celerik :component="$grid" />
```

```twig [Twig]
{{ celerik(grid) }}
```

```php [Plain PHP]
<?= $grid ?>
```

:::

The grid works without JavaScript too: PHP renders a real `<table>` with its caption and header scopes, and the bundle turns it into a sortable, searchable, paged grid.

## Next steps

- [How it works](./how-it-works): rendering, options and the JavaScript API.
- [Data endpoints](./data): server-side grids and calendars with `respond()`.
- Set up [plain PHP](./plain-php), [Laravel](./laravel) or [Symfony](./symfony).
- Browse the [components](../components/).
