<?php

/**
 * Test page for the browser accessibility tests (tests/browser/*.spec.mjs), started by
 * Playwright's webServer:
 *
 *     php -S 127.0.0.1:8123 tests/browser/server.php
 *
 *   GET /?theme=material&mode=dark   every component on one page
 *   GET /assets/...                  the bundle in public/ (run "npm run build" first)
 *   GET /vendor/...                  Bootstrap, jQuery and axe-core from node_modules
 *   GET /elfinder/...                elFinder's assets from vendor/studio-42/elfinder
 *   GET|POST /files/connector        the File Manager connector, on a temporary folder
 *
 * Everything is served locally so results do not depend on a CDN.
 */

declare(strict_types=1);

use Celerik\Ui\Assets;
use Celerik\Ui\Components\Chart;
use Celerik\Ui\Components\FileManager;
use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Grid;
use Celerik\Ui\Components\Modal;
use Celerik\Ui\Components\Notification;
use Celerik\Ui\Components\Scheduler;
use Celerik\Ui\Components\Tabs;
use Celerik\Ui\FileManager\FileManagerConfig;
use Celerik\Ui\FileManager\FileManagerConnector;
use Celerik\Ui\Support\Html;
use Celerik\Ui\Upload\UploadRules;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$static = [
    '/assets/' => $root . '/public',
    '/elfinder/' => $root . '/vendor/studio-42/elfinder',
    '/vendor/bootstrap/' => $root . '/node_modules/bootstrap/dist',
    '/vendor/jquery/' => $root . '/node_modules/jquery/dist',
    '/vendor/axe-core/' => $root . '/node_modules/axe-core',
];
foreach ($static as $prefix => $directory) {
    if (!str_starts_with($path, $prefix)) {
        continue;
    }
    $base = realpath($directory);
    $file = realpath($directory . '/' . substr($path, strlen($prefix)));
    if ($base === false || $file === false || !is_file($file) || !str_starts_with($file, $base . DIRECTORY_SEPARATOR)) {
        http_response_code(404);
        exit('Not found. Run "composer install", "npm install" and "npm run build".');
    }
    $types = [
        'css' => 'text/css', 'js' => 'text/javascript', 'map' => 'application/json', 'png' => 'image/png',
        'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'mp3' => 'audio/mpeg', 'wav' => 'audio/wav',
        'json' => 'application/json', 'woff' => 'font/woff', 'woff2' => 'font/woff2',
    ];
    header('Content-Type: ' . ($types[pathinfo($file, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
    readfile($file);
    exit;
}

$filesRoot = sys_get_temp_dir() . '/celerik-browser-tests/files';
if (!is_dir($filesRoot)) {
    mkdir($filesRoot . '/Reports', 0700, true);
    file_put_contents($filesRoot . '/welcome.txt', "Test file.\n");
}

if ($path === '/files/connector') {
    FileManagerConnector::run(
        FileManagerConfig::local($filesRoot, UploadRules::images())->alias('Shared files'),
        authorized: true,
    );
}

if ($path !== '/') {
    http_response_code(404);
    exit('Not found.');
}

$theme = in_array($_GET['theme'] ?? '', Assets::THEMES, true) ? $_GET['theme'] : 'bootstrap';
$mode = ($_GET['mode'] ?? '') === 'dark' ? 'dark' : 'light';

$components = [
    Grid::make('users')
        ->caption('Users')
        ->column('id', 'ID')
        ->column('name')
        ->column('email', 'E-mail')
        ->export('copy', 'csv')
        ->pageSize(2)
        ->pageSizes([2, 10])
        ->rows([
            ['id' => 1, 'name' => 'Ada Lovelace', 'email' => 'ada@example.com'],
            ['id' => 2, 'name' => 'Alan Turing', 'email' => 'alan@example.com'],
            ['id' => 3, 'name' => 'Grace Hopper', 'email' => 'grace@example.com'],
        ]),

    Chart::make('revenue')
        ->type('line')
        ->title('Revenue per region')
        ->labels(['Jan', 'Feb', 'Mar', 'Apr'])
        ->dataset('North', [1200, 1900, 1500, 2100])
        ->dataset('South', [900, 1100, 1700, 1600])
        ->dataset('East', [600, 800, 1000, 1400])
        ->height(260),

    Form::make('signup')
        ->action('/signup')
        ->csrf('test-token')
        ->add(
            Field::text('name')->required()->maxLength(80),
            Field::email('email')->required()->help('We never share your e-mail.'),
            Field::tel('phone')->mask('(999) 999-9999'),
            Field::select('country', ['fr' => 'France', 'ca' => 'Canada'])->placeholder('Choose...'),
            Field::checkbox('terms', 'I accept the terms')->required(),
        )
        ->errors(['email' => 'This e-mail is already registered.'])
        ->submit('Create account'),

    Form::make('article')
        ->action('/articles')
        ->csrf('test-token')
        ->add(
            Field::text('title')->required(),
            Field::editor('body', 'Article')->required()->value('<h2>Intro</h2><p>Hello <strong>world</strong></p>'),
            Field::file('cover', 'Cover image')->rules(UploadRules::images()->maxSize('2M'))->upload('/uploads'),
        )
        ->submit('Publish'),

    Scheduler::make('team-calendar')
        ->title('Team calendar')
        ->view('month')
        ->date('2026-11-05')
        ->timeZone('UTC')
        ->firstDay(1)
        ->events([
            ['id' => 1, 'title' => 'Kick-off', 'start' => '2026-11-03T09:00:00Z', 'end' => '2026-11-03T10:00:00Z'],
            ['id' => 2, 'title' => 'Design review', 'start' => '2026-11-05T14:00:00Z', 'end' => '2026-11-05T15:30:00Z', 'color' => '#ffc107'],
            ['id' => 3, 'title' => 'Holiday', 'start' => '2026-11-06'],
        ])
        ->updateUrl('/events/update')
        ->csrf('test-token'),

    Tabs::make('account')
        ->label('Account settings')
        ->tab('profile', 'Profile', 'Your name and e-mail.')
        ->tab('security', 'Security', Html::raw('<p>Change your password.</p>'))
        ->tab('billing', 'Billing', 'Invoices and payment methods.'),

    FileManager::make('files')
        ->title('Shared files')
        ->url('/files/connector')
        ->assetsUrl('/elfinder')
        ->height(380),
];

$modal = Modal::make('contact-modal')
    ->title('Contact us')
    ->body(Form::make('modal-form')->add(Field::email('contact_email', 'E-mail')->required())->submit(null))
    ->cancelButton()
    ->button('Send', 'primary', attributes: ['type' => 'submit', 'form' => 'modal-form']);

$notification = Notification::make('flash')
    ->success('Profile saved.')
    ->error('The export failed.');
?>
<!doctype html>
<html lang="en" data-bs-theme="<?= $mode ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="data:,">
    <title>Celerik browser tests (<?= $theme ?>, <?= $mode ?>)</title>
    <link rel="stylesheet" href="/vendor/bootstrap/css/bootstrap.min.css">
    <script src="/vendor/jquery/jquery.min.js" defer></script>
    <script src="/vendor/bootstrap/js/bootstrap.bundle.min.js" defer></script>
    <?= Assets::tags('/assets', theme: $theme) ?>
</head>
<body>
<main class="container py-4">
    <h1>Celerik components</h1>
    <?php foreach ($components as $component) : ?>
        <section class="my-4"><?= $component->render() ?></section>
    <?php endforeach ?>
    <section class="my-4">
        <?= $modal->trigger('Contact us') ?>
        <?= $modal->render() ?>
    </section>
    <?= $notification->render() ?>
</main>
</body>
</html>
