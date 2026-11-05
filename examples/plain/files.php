<?php

/**
 * File manager (elFinder), included by index.php after session_start().
 *
 *   GET  /elfinder/{css,img,js,sounds}/...   elFinder's own assets, from vendor/studio-42/elfinder
 *   GET|POST /files/connector                the elFinder connector
 *
 * The managed folder is in the system temp directory, outside the web root. This demo only
 * authorizes requests from this computer: use your application's authorization instead.
 */

declare(strict_types=1);

use Celerik\Ui\Components\FileManager;
use Celerik\Ui\FileManager\FileManagerConfig;
use Celerik\Ui\FileManager\FileManagerConnector;
use Celerik\Ui\Upload\UploadRules;

/** @var string $path */

$elfinder = dirname(__DIR__, 2) . '/vendor/studio-42/elfinder';

if (preg_match('~^/elfinder/(css|img|js|sounds)/~', $path) === 1) {
    $base = realpath($elfinder);
    $file = realpath($elfinder . substr($path, strlen('/elfinder')));
    if ($base === false || $file === false || !is_file($file) || !str_starts_with($file, $base . DIRECTORY_SEPARATOR)) {
        http_response_code(404);
        exit('Not found. Run "composer install" (studio-42/elfinder is a dev dependency).');
    }
    $types = [
        'css' => 'text/css', 'js' => 'text/javascript', 'png' => 'image/png', 'gif' => 'image/gif',
        'svg' => 'image/svg+xml', 'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'json' => 'application/json',
    ];
    header('Content-Type: ' . ($types[pathinfo($file, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
    header('X-Content-Type-Options: nosniff');
    readfile($file);
    exit;
}

$filesRoot = sys_get_temp_dir() . '/celerik-example/files';
if (!is_dir($filesRoot)) {
    mkdir($filesRoot . '/Reports', 0700, true);
    file_put_contents($filesRoot . '/welcome.txt', "Files uploaded here are checked like UploadHandler does.\n");
    file_put_contents($filesRoot . '/.secret', 'Dotfiles are hidden and cannot be read.');
}

if ($path === '/files/connector') {
    $local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

    FileManagerConnector::run(
        FileManagerConfig::local($filesRoot, UploadRules::images()->withType('application/pdf', ['pdf'])->maxSize('5M'))
            ->alias('Shared files'),
        authorized: $local,
    );
}

$fileManager = FileManager::make('files')
    ->title('Shared files')
    ->url('/files/connector')
    ->assetsUrl('/elfinder')
    ->height(420);
