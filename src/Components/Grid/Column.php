<?php

declare(strict_types=1);

namespace Celerik\Ui\Components\Grid;

use Celerik\Ui\Data\DataRequest;
use InvalidArgumentException;

final class Column
{
    private string $title;
    private bool $sortable = true;
    private bool $searchable = true;
    private bool $visible = true;
    private ?string $className = null;
    private ?string $width = null;

    public function __construct(private readonly string $field, ?string $title = null)
    {
        if (!DataRequest::isValidField($field)) {
            throw new InvalidArgumentException(sprintf('Invalid column field "%s".', $field));
        }

        $this->title = $title ?? ucfirst(str_replace(['_', '.'], ' ', $field));
    }

    public static function make(string $field, ?string $title = null): self
    {
        return new self($field, $title);
    }

    public function sortable(bool $sortable = true): self
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Hidden columns are still sent to the client (e.g. an id for row actions).
     */
    public function hidden(bool $hidden = true): self
    {
        $this->visible = !$hidden;

        return $this;
    }

    public function className(string $className): self
    {
        $this->className = $className;

        return $this;
    }

    public function width(string $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    /**
     * @return array<string, string|bool>
     */
    public function toArray(): array
    {
        return array_filter([
            'field' => $this->field,
            'title' => $this->title,
            'sortable' => $this->sortable,
            'searchable' => $this->searchable,
            'visible' => $this->visible,
            'className' => $this->className,
            'width' => $this->width,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
