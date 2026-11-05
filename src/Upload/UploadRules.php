<?php

declare(strict_types=1);

namespace Celerik\Ui\Upload;

use InvalidArgumentException;

/**
 * Which files an upload field accepts: MIME types with their allowed extensions, a size
 * limit and a file count. The same rules drive the field (accept attribute, client-side
 * checks, help text) and the server (UploadHandler).
 */
final class UploadRules
{
    /** Common types and their extensions; use withType() for others. */
    public const KNOWN_TYPES = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp'],
        'image/avif' => ['avif'],
        'application/pdf' => ['pdf'],
        'text/plain' => ['txt'],
        'text/csv' => ['csv'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['pptx'],
        'application/vnd.oasis.opendocument.text' => ['odt'],
        'application/zip' => ['zip'],
    ];

    /**
     * Never accepted, whatever the configuration: server-side code, executables, and
     * formats that can carry scripts (HTML, SVG, XML). Also checked in double extensions.
     */
    public const FORBIDDEN_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'pht', 'phps', 'inc',
        'asp', 'aspx', 'jsp', 'cgi', 'pl', 'py', 'rb', 'sh', 'bash',
        'exe', 'com', 'bat', 'cmd', 'msi', 'dll', 'so',
        'js', 'mjs', 'html', 'htm', 'xhtml', 'shtml', 'svg', 'svgz', 'xml', 'xsl',
        'htaccess', 'htpasswd', 'ini', 'user.ini',
    ];

    private const FORBIDDEN_TYPES = [
        'text/html', 'image/svg+xml', 'application/xhtml+xml', 'text/xml', 'application/xml',
    ];

    public const DEFAULT_MAX_SIZE = 5 * 1024 * 1024;

    /**
     * @param array<string, list<string>> $types MIME type => extensions
     */
    private function __construct(
        public readonly array $types,
        public readonly int $maxSize = self::DEFAULT_MAX_SIZE,
        public readonly int $maxFiles = 1,
    ) {
    }

    /**
     * Rules for known MIME types, e.g. UploadRules::types('image/png', 'application/pdf').
     */
    public static function types(string ...$mimeTypes): self
    {
        if ($mimeTypes === []) {
            throw new InvalidArgumentException('At least one MIME type is required.');
        }

        $rules = new self([]);
        foreach ($mimeTypes as $mimeType) {
            if (!isset(self::KNOWN_TYPES[$mimeType])) {
                throw new InvalidArgumentException(sprintf(
                    'Unknown MIME type "%s": use withType() with its extensions.',
                    $mimeType,
                ));
            }
            $rules = $rules->withType($mimeType, self::KNOWN_TYPES[$mimeType]);
        }

        return $rules;
    }

    /**
     * JPEG, PNG, GIF and WebP images.
     */
    public static function images(): self
    {
        return self::types('image/jpeg', 'image/png', 'image/gif', 'image/webp');
    }

    /**
     * PDF, Word, Excel, OpenDocument text and plain text documents.
     */
    public static function documents(): self
    {
        return self::types(
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.oasis.opendocument.text',
            'text/plain',
        );
    }

    /**
     * @param list<string> $extensions Lowercase, without the dot.
     */
    public function withType(string $mimeType, array $extensions): self
    {
        $validType = preg_match('~^[a-z]+/[a-z0-9.+-]+$~', $mimeType) === 1;
        if (!$validType || in_array($mimeType, self::FORBIDDEN_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('MIME type "%s" cannot be allowed.', $mimeType));
        }

        foreach ($extensions as $extension) {
            $validExtension = preg_match('/^[a-z0-9]{1,10}$/', $extension) === 1;
            if (!$validExtension || in_array($extension, self::FORBIDDEN_EXTENSIONS, true)) {
                throw new InvalidArgumentException(sprintf('Extension "%s" cannot be allowed.', $extension));
            }
        }

        if ($extensions === []) {
            throw new InvalidArgumentException(sprintf('MIME type "%s" needs at least one extension.', $mimeType));
        }

        $types = $this->types;
        $types[$mimeType] = array_values(array_unique([...($types[$mimeType] ?? []), ...$extensions]));

        return new self($types, $this->maxSize, $this->maxFiles);
    }

    /**
     * Maximum size per file, in bytes or as "500K", "5M", "1G".
     */
    public function maxSize(int|string $size): self
    {
        $bytes = is_int($size) ? $size : self::parseSize($size);

        if ($bytes < 1) {
            throw new InvalidArgumentException('The maximum size must be positive.');
        }

        return new self($this->types, $bytes, $this->maxFiles);
    }

    public function maxFiles(int $maxFiles): self
    {
        if ($maxFiles < 1 || $maxFiles > 100) {
            throw new InvalidArgumentException('The maximum number of files must be between 1 and 100.');
        }

        return new self($this->types, $this->maxSize, $maxFiles);
    }

    public function allows(string $mimeType, string $extension): bool
    {
        return in_array(strtolower($extension), $this->types[$mimeType] ?? [], true);
    }

    /**
     * Value for the input's accept attribute, e.g. "image/png,.png,application/pdf,.pdf".
     */
    public function accept(): string
    {
        $accept = [];
        foreach ($this->types as $mimeType => $extensions) {
            $accept[] = $mimeType;
            foreach ($extensions as $extension) {
                $accept[] = '.' . $extension;
            }
        }

        return implode(',', $accept);
    }

    /**
     * Help text describing the rules, e.g. "JPG, JPEG, PNG. Up to 5 MB per file."
     */
    public function describe(): string
    {
        $extensions = array_unique(array_merge(...array_values($this->types)));
        $text = strtoupper(implode(', ', $extensions)) . '. Up to ' . self::formatSize($this->maxSize);

        return $text . ($this->maxFiles > 1 ? sprintf(' per file, %d files at most.', $this->maxFiles) : '.');
    }

    public static function formatSize(int $bytes): string
    {
        foreach (['GB' => 1024 ** 3, 'MB' => 1024 ** 2, 'KB' => 1024] as $unit => $factor) {
            if ($bytes >= $factor) {
                return rtrim(rtrim(number_format($bytes / $factor, 1, '.', ''), '0'), '.') . ' ' . $unit;
            }
        }

        return $bytes . ' bytes';
    }

    private static function parseSize(string $size): int
    {
        if (preg_match('/^(\d+)\s*([KMG])B?$/i', trim($size), $match) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid size "%s": use bytes or e.g. "500K", "5M".', $size));
        }

        $power = ['K' => 1, 'M' => 2, 'G' => 3][strtoupper($match[2])];

        return (int) $match[1] * 1024 ** $power;
    }
}
