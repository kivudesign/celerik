<?php

declare(strict_types=1);

namespace Celerik\Ui\Upload;

use JsonSerializable;

/**
 * A validated file in its final location.
 */
final class StoredFile implements JsonSerializable
{
    /**
     * @param string $name Random stored file name (e.g. "3f2a…9c.pdf"), safe to use in paths.
     * @param string $originalName Sanitized client name, for display only: never use it in a path.
     */
    public function __construct(
        public readonly string $path,
        public readonly string $name,
        public readonly string $originalName,
        public readonly string $mimeType,
        public readonly int $size,
    ) {
    }

    /**
     * @return array{name: string, originalName: string, mimeType: string, size: int}
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'originalName' => $this->originalName,
            'mimeType' => $this->mimeType,
            'size' => $this->size,
        ];
    }
}
