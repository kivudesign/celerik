<?php

/**
 * Renders the live demos of the documentation site (npm run docs:demos, run before
 * docs:dev and docs:build):
 *
 *   site/demos/<name>.php            returns a component, or a list of components
 *   site/public/demos/<name>.html    a standalone page with that component (generated)
 *   site/public/assets/              the Celerik bundle, Bootstrap and jQuery (copied)
 *
 * Pages embed a demo with <Demo name="grid" /> and show its code with <<< @/demos/grid.php.
 * Demos run without a server: data is embedded and nothing is saved.
 */

declare(strict_types=1);

use Celerik\Ui\Assets;
use Celerik\Ui\Support\Html;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

$public = __DIR__ . '/../public';

// Copies a directory (the bundle, vendor assets) into the site's public directory.
$copyDirectory = static function (string $from, string $to): void {
    if (!is_dir($from)) {
        fwrite(STDERR, "Missing $from: run npm install and npm run build first.\n");
        exit(1);
    }
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        $target = $to . substr($file->getPathname(), strlen($from));
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }
        copy($file->getPathname(), $target);
    }
};

$copyDirectory($root . '/public', $public . '/assets/celerik');
$copyDirectory($root . '/node_modules/bootstrap/dist', $public . '/assets/bootstrap');
$copyDirectory($root . '/node_modules/jquery/dist', $public . '/assets/jquery');
copy(__DIR__ . '/runtime.js', $public . '/assets/demo-runtime.js');

if (!is_dir($public . '/demos')) {
    mkdir($public . '/demos', 0755, true);
}

$count = 0;
foreach (glob(__DIR__ . '/*.php') ?: [] as $file) {
    $name = basename($file, '.php');
    if ($name === 'build') {
        continue;
    }
    $demo = require $file;
    $components = is_array($demo) ? $demo : [$demo];
    $title = ucwords(str_replace('-', ' ', $name));

    $body = '';
    foreach ($components as $component) {
        $body .= Html::tag('div', ['class' => 'mb-4'], (string) $component);
    }

    $html = '<!doctype html>' . "\n"
        . '<html lang="en">' . "\n"
        . '<head>' . "\n"
        . '<meta charset="utf-8">' . "\n"
        . '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n"
        . '<title>' . Html::escape($title) . ' — Celerik UI demo</title>' . "\n"
        . '<link rel="icon" href="data:,">' . "\n"
        . '<link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">' . "\n"
        . '<script src="../assets/jquery/jquery.min.js" defer></script>' . "\n"
        . '<script src="../assets/bootstrap/js/bootstrap.bundle.min.js" defer></script>' . "\n"
        . Assets::tags('../assets/celerik') . "\n"
        // After celerik.css: sets data-bs-theme and adds the base look from ?mode= and ?theme=.
        . '<script src="../assets/demo-runtime.js"></script>' . "\n"
        . '</head>' . "\n"
        . '<body>' . "\n"
        . '<main class="container-fluid p-3">' . "\n"
        . '<h1 class="visually-hidden">' . Html::escape($title) . ' demo</h1>' . "\n"
        . $body . "\n"
        . '</main>' . "\n"
        . '</body>' . "\n"
        . '</html>' . "\n";

    file_put_contents($public . '/demos/' . $name . '.html', $html);
    $count++;
}

echo "Wrote $count demos to site/public/demos\n";
