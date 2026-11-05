<?php

declare(strict_types=1);

namespace Celerik\Ui\Upload;

use finfo;
use RuntimeException;

/**
 * Validates and stores uploads.
 *
 * Two ways to use it:
 * - Asynchronous (Field::file()->upload($url), FilePond): the endpoint calls process() and
 *   answers the returned id as text/plain; DELETE calls revert(). When the form is
 *   submitted, the field holds the ids and the controller calls claim() for each one.
 * - Classic form post (no upload URL): the controller calls store() for each file.
 *
 * Validation: upload errors, empty files, size limit, the real MIME type (read from the
 * content with finfo, never trusted from the browser), the extension matching that type,
 * and forbidden extensions anywhere in the name (`photo.php.jpg` is refused). Stored files
 * get random names. Keep both directories outside the web root and serve files through a
 * controller that checks access.
 */
final class UploadHandler
{
    private const ID_PATTERN = '/^[a-f0-9]{32}$/';

    public function __construct(
        private readonly UploadRules $rules,
        private readonly string $temporaryDirectory,
    ) {
    }

    /**
     * Validates the file and keeps it in the temporary directory.
     *
     * @return string Opaque id (32 hex characters) to send back to FilePond.
     * @throws UploadException
     */
    public function process(UploadedFile $file): string
    {
        $checked = $this->validate($file);
        $id = bin2hex(random_bytes(16));
        $directory = $this->directory($this->temporaryDirectory);

        $this->move($file->path, $directory . '/' . $id);
        $this->writeMeta($directory . '/' . $id . '.json', $checked + ['created' => time()]);

        return $id;
    }

    /**
     * Deletes a temporary upload (FilePond's "revert"). Unknown ids are ignored.
     */
    public function revert(string $id): bool
    {
        if (preg_match(self::ID_PATTERN, $id) !== 1) {
            return false;
        }

        $path = rtrim($this->temporaryDirectory, '/\\') . '/' . $id;
        $existed = is_file($path);
        @unlink($path);
        @unlink($path . '.json');

        return $existed;
    }

    /**
     * Moves a temporary upload to its final directory, after checking it again.
     *
     * @throws UploadException When the id is unknown, expired or no longer valid.
     */
    public function claim(string $id, string $directory): StoredFile
    {
        $id = trim($id);
        $path = rtrim($this->temporaryDirectory, '/\\') . '/' . $id;

        if (preg_match(self::ID_PATTERN, $id) !== 1 || !is_file($path) || !is_file($path . '.json')) {
            throw new UploadException('The uploaded file has expired. Please upload it again.');
        }

        $meta = json_decode((string) file_get_contents($path . '.json'), true);
        $mimeType = $this->mimeType($path);

        if (
            !is_array($meta)
            || !is_string($meta['extension'] ?? null)
            || $mimeType !== ($meta['mimeType'] ?? null)
            || !$this->rules->allows($mimeType, $meta['extension'])
        ) {
            $this->revert($id);
            throw new UploadException('This file type is not allowed.');
        }

        $stored = $this->storeAs($path, $directory, [
            'extension' => $meta['extension'],
            'originalName' => (string) ($meta['originalName'] ?? 'file'),
            'mimeType' => $mimeType,
        ]);
        @unlink($path . '.json');

        return $stored;
    }

    /**
     * Validates the file and moves it straight to its final directory (no FilePond).
     *
     * @throws UploadException
     */
    public function store(UploadedFile $file, string $directory): StoredFile
    {
        return $this->storeAs($file->path, $directory, $this->validate($file));
    }

    /**
     * Deletes temporary uploads older than $maxAge seconds (never claimed). Run it from a cron job.
     *
     * @return int Number of uploads deleted.
     */
    public function purge(int $maxAge = 86400): int
    {
        $deleted = 0;

        foreach (glob(rtrim($this->temporaryDirectory, '/\\') . '/*.json') ?: [] as $metaPath) {
            if (filemtime($metaPath) < time() - $maxAge && $this->revert(basename($metaPath, '.json'))) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * @return array{originalName: string, mimeType: string, extension: string, size: int}
     * @throws UploadException
     */
    private function validate(UploadedFile $file): array
    {
        if ($file->error !== UPLOAD_ERR_OK) {
            throw new UploadException(match ($file->error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file is too large.',
                UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded. Please try again.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                default => 'The file could not be uploaded.',
            });
        }

        if (!is_file($file->path)) {
            throw new UploadException('No file was uploaded.');
        }

        $size = (int) filesize($file->path);
        if ($size === 0) {
            throw new UploadException('The file is empty.');
        }
        if ($size > $this->rules->maxSize) {
            throw new UploadException(sprintf(
                'The file is too large (%s at most).',
                UploadRules::formatSize($this->rules->maxSize),
            ));
        }

        $originalName = self::sanitizeName($file->clientName);
        $segments = explode('.', strtolower($originalName));
        $extension = count($segments) > 1 ? (string) end($segments) : '';

        // Every segment after the first is checked: "invoice.php.pdf" is refused.
        foreach (array_slice($segments, 1) as $segment) {
            if (in_array($segment, UploadRules::FORBIDDEN_EXTENSIONS, true)) {
                throw new UploadException('This file type is not allowed.');
            }
        }

        $mimeType = $this->mimeType($file->path);
        if (!isset($this->rules->types[$mimeType])) {
            throw new UploadException('This file type is not allowed.');
        }
        if (!$this->rules->allows($mimeType, $extension)) {
            throw new UploadException('The file extension does not match its content.');
        }

        return ['originalName' => $originalName, 'mimeType' => $mimeType, 'extension' => $extension, 'size' => $size];
    }

    /**
     * @param array{extension: string, originalName: string, mimeType: string} $file
     */
    private function storeAs(string $source, string $directory, array $file): StoredFile
    {
        $name = bin2hex(random_bytes(16)) . '.' . $file['extension'];
        $target = $this->directory($directory) . '/' . $name;

        $this->move($source, $target);

        return new StoredFile($target, $name, $file['originalName'], $file['mimeType'], (int) filesize($target));
    }

    private function move(string $from, string $to): void
    {
        $moved = is_uploaded_file($from) ? move_uploaded_file($from, $to) : rename($from, $to);

        if (!$moved) {
            throw new RuntimeException(sprintf('Could not move the uploaded file to "%s".', $to));
        }

        @chmod($to, 0640);
    }

    private function mimeType(string $path): string
    {
        return (string) (new finfo(FILEINFO_MIME_TYPE))->file($path);
    }

    private function directory(string $directory): string
    {
        $directory = rtrim($directory, '/\\');

        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Could not create the directory "%s".', $directory));
        }

        if (!is_writable($directory)) {
            throw new RuntimeException(sprintf('The directory "%s" is not writable.', $directory));
        }

        return $directory;
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function writeMeta(string $path, array $meta): void
    {
        file_put_contents($path, json_encode($meta, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /**
     * A display-only version of the client's file name: no path, no control characters.
     */
    private static function sanitizeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = (string) preg_replace('/[\x00-\x1F\x7F]/u', '', $name);
        $name = trim($name, " .\t");

        return $name === '' ? 'file' : mb_substr($name, -200);
    }
}
