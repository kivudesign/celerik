# Plain PHP

Celerik needs no framework: components are plain PHP objects and `Assets::tags()` writes the asset tags.

## Assets

Copy the bundle to your web root, and again after each update, for example in a deployment script:

```bash
cp -r vendor/celerik/ui/public public/vendor/celerik
```

Or skip the copy and load it from the CDN: `Assets::tags(Assets::cdnUrl())` (see [Getting started](./getting-started#or-use-the-cdn-or-npm)).

```php
<?= Celerik\Ui\Assets::tags('/vendor/celerik') ?>
<?= Celerik\Ui\Assets::tags('/vendor/celerik', theme: 'material') ?>
<?= Celerik\Ui\Assets::tags('/vendor/celerik', nonce: $cspNonce) ?>
```

## A page with a server-side grid

```php
<?php
require __DIR__ . '/../vendor/autoload.php';

use Celerik\Ui\Assets;
use Celerik\Ui\Components\Grid;
use Celerik\Ui\Data\ArrayDataSource;

$grid = Grid::make('people')
    ->caption('People')
    ->url('/people.json')
    ->column('name')
    ->column('email', 'E-mail');

if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/people.json') {
    header('Content-Type: application/json');
    echo json_encode($grid->respond(new ArrayDataSource(require 'people.php'), $_GET));
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>People</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <?= Assets::tags('/vendor/celerik') ?>
</head>
<body>
<main class="container py-4">
    <?= $grid ?>
</main>
</body>
</html>
```

## Forms and CSRF

Give forms a token and check it with `hash_equals()`. Pass server-side errors and the old input back, so the form shows them:

```php
$form = Form::make('contact')
    ->action('/contact')
    ->csrf($_SESSION['csrf'])
    ->add(Field::email('email')->required(), Field::textarea('message')->required()->minLength(10))
    ->errors($errors)       // ['email' => 'Please enter a valid e-mail address.']
    ->values($_POST);
```

Client-side validation is a convenience only: always validate again on the server.

## The example

`examples/plain/` in the repository is a complete application: a server-side grid, a chart, an editable calendar, forms with a rich text editor and uploads, tabs, a modal, notifications, a file manager and the theme builder.

```bash
composer install && npm install && npm run build
php -S localhost:8000 examples/plain/index.php
```
