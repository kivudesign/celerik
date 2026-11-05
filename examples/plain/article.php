<?php

/**
 * Rich text editor and file uploads, included by index.php after session_start().
 *
 *   POST   /upload/{cover|attachments}   FilePond "process": validates the file, answers its id (text/plain)
 *   DELETE /upload/{cover|attachments}   FilePond "revert": the body is the id
 *   POST   /article                      the form: claims the uploaded ids, sanitizes the HTML
 *
 * Uploads must carry the CSRF token in the X-CSRF-TOKEN header (FilePond sends it).
 * Files are stored outside the web root, in the system temp directory, for this demo.
 */

declare(strict_types=1);

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Support\EditorHtml;
use Celerik\Ui\Upload\StoredFile;
use Celerik\Ui\Upload\UploadedFile;
use Celerik\Ui\Upload\UploadException;
use Celerik\Ui\Upload\UploadHandler;
use Celerik\Ui\Upload\UploadRules;

/** @var string $path */

$storage = sys_get_temp_dir() . '/celerik-example';
$uploadRules = [
    'cover' => UploadRules::images()->maxSize('2M'),
    'attachments' => UploadRules::documents()->maxSize('5M')->maxFiles(3),
];
$handler = static fn (string $field): UploadHandler => new UploadHandler($uploadRules[$field], $storage . '/tmp');

if (preg_match('~^/upload/(cover|attachments)$~', $path, $match) === 1) {
    header('Content-Type: text/plain');

    if (!hash_equals($_SESSION['csrf'], (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'DELETE') {
        $handler($match[1])->revert(trim((string) file_get_contents('php://input')));
        exit;
    }

    try {
        $files = UploadedFile::fromGlobals(current($_FILES) ?: []);
        if ($method !== 'POST' || count($files) !== 1) {
            throw new UploadException('Send exactly one file.');
        }
        echo $handler($match[1])->process($files[0]);
    } catch (UploadException $e) {
        http_response_code(422);
        echo $e->getMessage();
    }
    exit;
}

$articleErrors = [];
$article = null;

if ($path === '/article' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['_token'] ?? ''))) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $body = EditorHtml::sanitize((string) ($_POST['body'] ?? ''));

    if ($title === '') {
        $articleErrors['title'] = 'Please enter a title.';
    }
    if (trim(strip_tags($body)) === '') {
        $articleErrors['body'] = 'Please write the article.';
    }

    /**
     * FilePond submits upload ids; without JavaScript the browser submits the files themselves.
     *
     * @return list<StoredFile>
     */
    $receive = static function (string $field) use ($handler, $storage): array {
        $directory = $storage . '/' . $field;
        if (isset($_FILES[$field])) {
            return array_map(
                static fn (UploadedFile $file): StoredFile => $handler($field)->store($file, $directory),
                UploadedFile::fromGlobals($_FILES[$field]),
            );
        }

        $ids = array_filter((array) ($_POST[$field] ?? []), 'is_string');

        return array_map(static fn (string $id): StoredFile => $handler($field)->claim($id, $directory), $ids);
    };

    try {
        $cover = $receive('cover');
        if ($cover === []) {
            $articleErrors['cover'] = 'Please add a cover image.';
        }
        $attachments = array_slice($receive('attachments'), 0, $uploadRules['attachments']->maxFiles);
    } catch (UploadException $e) {
        $articleErrors['cover'] ??= $e->getMessage();
        $cover = $attachments = [];
    }

    if ($articleErrors === []) {
        $article = ['title' => $title, 'body' => $body, 'cover' => $cover[0], 'attachments' => $attachments];
    }
}

$articleForm = Form::make('article')
    ->action('/article')
    ->csrf($_SESSION['csrf'])
    ->add(
        Field::text('title')->required()->maxLength(120),
        Field::editor('body', 'Article')->required()->placeholder('Write your article…'),
        Field::file('cover', 'Cover image')->rules($uploadRules['cover'])->upload('/upload/cover')->required(),
        Field::file('attachments')->rules($uploadRules['attachments'])->upload('/upload/attachments'),
    )
    ->errors($articleErrors)
    ->values($article === null ? $_POST : [])
    ->submit('Publish');
