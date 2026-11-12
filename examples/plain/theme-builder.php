<?php

/**
 * Theme builder, included by index.php after session_start().
 *
 *   GET  /theme-builder          the ThemeBuilder, previewing this example's components
 *   POST /theme-builder/save     saves the theme in the session (JSON, or the form without JS)
 *   GET  /custom-theme.css       the saved theme's stylesheet (Theme::toCss())
 *
 * The builder is a development tool: this demo only allows it from this computer. In an
 * application, put it behind your authorization or a local-only route, and save themes in
 * a file or your database.
 */

declare(strict_types=1);

use Celerik\Ui\Assets;
use Celerik\Ui\Components\ThemeBuilder;
use Celerik\Ui\Theme\Theme;

/** @var string $path */
/** @var string $theme */
/** @var Celerik\Ui\Components\Grid $grid */
/** @var Celerik\Ui\Components\Chart $chart */
/** @var Celerik\Ui\Components\Form $form */
/** @var Celerik\Ui\Components\Tabs $tabs */

$local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
$savedTheme = isset($_SESSION['theme']) ? Theme::fromArray($_SESSION['theme']) : null;

if ($path === '/custom-theme.css') {
    header('Content-Type: text/css');
    echo $savedTheme?->toCss() ?? '';
    exit;
}

if ($path === '/theme-builder/save' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_token'] ?? '';
    if (!$local || !hash_equals($_SESSION['csrf'], (string) $token)) {
        http_response_code(403);
        exit('Not allowed.');
    }
    try {
        $saved = ThemeBuilder::fromRequest($_POST, (string) file_get_contents('php://input'));
    } catch (InvalidArgumentException $exception) {
        http_response_code(422);
        exit($exception->getMessage());
    }
    $_SESSION['theme'] = $saved->toArray();

    if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
        header('Content-Type: application/json');
        echo json_encode(['data' => $saved->toArray(), 'meta' => []]);
    } else {
        header('Location: /?custom=1', true, 303); // without JavaScript: show the saved theme
    }
    exit;
}

if ($path !== '/theme-builder') {
    return;
}

if (!$local) {
    http_response_code(403);
    exit('The theme builder is only available from this computer.');
}

$builder = ThemeBuilder::make('builder')
    ->theme($savedTheme ?? Theme::preset($theme))
    ->assetsUrl('/assets')
    ->saveUrl('/theme-builder/save')
    ->csrf($_SESSION['csrf'])
    ->preview($form, $grid, $tabs, $chart);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Celerik UI — theme builder</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@4.0.0/dist/jquery.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <?= Assets::tags('/assets', theme: $builder->getTheme()->getBase()) ?>
</head>
<body>
<main class="container-fluid py-4">
    <h1 class="h3 mb-1">Celerik UI — theme builder</h1>
    <p class="mb-4">
        <a href="/">Back to the example</a>
        <?php if ($savedTheme !== null) : ?>
            · <a href="/?custom=1">Example with the saved theme</a>
        <?php endif ?>
    </p>
    <?= $builder ?>
</main>
</body>
</html>
<?php
exit;
