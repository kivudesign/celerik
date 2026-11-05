<?php

/**
 * Framework-free example: Grid (server-side), Chart, Scheduler and Form with plain PHP.
 *
 *     composer install && npm install && npm run build
 *     php -S localhost:8000 examples/plain/index.php
 */

declare(strict_types=1);

use Celerik\Ui\Assets;
use Celerik\Ui\Components\Chart;
use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Grid;
use Celerik\Ui\Data\ArrayDataSource;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Serve the prebuilt bundle (celerik.js, celerik.css, chunks/*) from the package's public/ directory.
if (str_starts_with($path, '/assets/')) {
    $public = realpath(dirname(__DIR__, 2) . '/public');
    $file = realpath($public . '/' . substr($path, strlen('/assets/')));
    $inside = $public !== false && $file !== false && str_starts_with($file, $public . DIRECTORY_SEPARATOR);
    if (!$inside || !is_file($file)) {
        http_response_code(404);
        exit('Asset not found. Run "npm run build" first.');
    }
    $types = ['css' => 'text/css', 'js' => 'text/javascript', 'map' => 'application/json'];
    header('Content-Type: ' . ($types[pathinfo($file, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
    readfile($file);
    exit;
}

$people = require __DIR__ . '/people.php';

$grid = Grid::make('people')
    ->caption('People')
    ->url('/people.json')
    ->column('id', 'ID', static fn ($column) => $column->searchable(false))
    ->column('name')
    ->column('email', 'E-mail')
    ->column('city')
    ->export('copy', 'csv', 'print');

if ($path === '/people.json') {
    header('Content-Type: application/json');
    echo json_encode($grid->respond(new ArrayDataSource($people), $_GET));
    exit;
}

$perCity = array_count_values(array_column($people, 'city'));
ksort($perCity);

$chart = Chart::make('cities')
    ->type('bar')
    ->title('People per city')
    ->labels(array_keys($perCity))
    ->dataset('People', array_values($perCity))
    ->height(280);

session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(16));

require __DIR__ . '/calendar.php';
require __DIR__ . '/article.php';

$errors = [];
$message = null;

if ($path === '/contact' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['_token'] ?? ''))) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }
    if (filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Please enter a valid e-mail address.';
    }
    if (mb_strlen(trim((string) ($_POST['message'] ?? ''))) < 10) {
        $errors['message'] = 'The message must be at least 10 characters.';
    }
    $message = $errors === [] ? 'Thanks, your message was sent.' : null;
}

$form = Form::make('contact')
    ->action('/contact')
    ->csrf($_SESSION['csrf'])
    ->add(
        Field::text('name')->required()->maxLength(80),
        Field::email('email')->required(),
        Field::tel('phone')->mask('(999) 999-9999')->help('Optional.'),
        Field::textarea('message')->required()->minLength(10),
    )
    ->errors($errors)
    ->values($message === null ? $_POST : [])
    ->submit('Send');

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Celerik UI — plain PHP example</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <?= Assets::tags('/assets') ?>
</head>
<body>
<main class="container py-4">
    <h1 class="h3 mb-4">Celerik UI — plain PHP</h1>

    <section class="mb-5" aria-labelledby="grid-title">
        <h2 id="grid-title" class="h5">Grid (server-side)</h2>
        <?= $grid ?>
    </section>

    <section class="mb-5" aria-labelledby="chart-title">
        <h2 id="chart-title" class="h5">Chart</h2>
        <?= $chart ?>
    </section>

    <section class="mb-5" aria-labelledby="scheduler-title">
        <h2 id="scheduler-title" class="h5">Scheduler</h2>
        <p class="text-body-secondary small">
            Drag or resize an event to move it, select a time range to create one, click an event to delete it.
        </p>
        <?= $scheduler ?>
    </section>

    <section class="mb-5" aria-labelledby="form-title">
        <h2 id="form-title" class="h5">Form</h2>
        <?php if ($message !== null) : ?>
            <div class="alert alert-success" role="status"><?= htmlspecialchars($message) ?></div>
        <?php endif ?>
        <?= $form ?>
    </section>

    <section class="mb-5" aria-labelledby="article-title">
        <h2 id="article-title" class="h5">Rich text and uploads</h2>
        <?php if ($article !== null) : ?>
            <div class="alert alert-success" role="status">
                <p class="mb-2">Published “<?= htmlspecialchars($article['title']) ?>”.</p>
                <p class="mb-2 small">
                    Cover: <?= htmlspecialchars($article['cover']->originalName) ?>
                    (<?= htmlspecialchars($article['cover']->mimeType) ?>,
                    stored as <?= htmlspecialchars($article['cover']->name) ?>)
                    <?php foreach ($article['attachments'] as $file) : ?>
                        <br>Attachment: <?= htmlspecialchars($file->originalName) ?>
                    <?php endforeach ?>
                </p>
                <?php /* Sanitized by EditorHtml::sanitize(), so it is safe to output as HTML. */ ?>
                <div class="border rounded p-2 bg-body"><?= $article['body'] ?></div>
            </div>
        <?php endif ?>
        <?= $articleForm ?>
    </section>
</main>
</body>
</html>
