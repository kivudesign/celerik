<?php

declare(strict_types=1);

namespace Celerik\Ui\Upload;

/**
 * A file received over HTTP, before validation.
 *
 * Plain PHP: UploadedFile::fromGlobals($_FILES['avatar']) checks is_uploaded_file().
 * Laravel / Symfony (already checked by the framework):
 *     new UploadedFile($file->getPathname(), $file->getClientOriginalName(), $file->getError())
 */
final class UploadedFile
{
    public function __construct(
        public readonly string $path,
        public readonly string $clientName,
        public readonly int $error = UPLOAD_ERR_OK,
    ) {
    }

    /**
     * Normalizes one $_FILES entry, single or multiple (`name="files[]"`).
     *
     * @param array<string, mixed> $entry
     * @return list<self>
     */
    public static function fromGlobals(array $entry): array
    {
        $names = (array) ($entry['name'] ?? []);
        $paths = (array) ($entry['tmp_name'] ?? []);
        $errors = (array) ($entry['error'] ?? []);

        $files = [];
        foreach ($names as $key => $name) {
            $path = (string) ($paths[$key] ?? '');
            $error = (int) ($errors[$key] ?? UPLOAD_ERR_NO_FILE);

            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            // Refuse paths that did not come from an HTTP upload (e.g. a forged $_FILES).
            if ($error === UPLOAD_ERR_OK && !is_uploaded_file($path)) {
                $error = UPLOAD_ERR_CANT_WRITE;
            }

            $files[] = new self($path, (string) $name, $error);
        }

        return $files;
    }
}
