<?php

declare(strict_types=1);

namespace Celerik\Ui;

use Celerik\Ui\Support\Html;
use InvalidArgumentException;
use Stringable;

/**
 * Base class for every Celerik UI component.
 *
 * Components render framework-neutral HTML. Client-side behaviour is attached by the
 * JS bundle, which looks for `data-celerik="<name>"` and reads the JSON stored in
 * `data-celerik-options`. No inline scripts are emitted, so pages work under a strict CSP.
 */
abstract class Component implements Stringable
{
    private static int $sequence = 0;

    protected readonly string $id;

    /** @var array<string, mixed> Raw options forwarded to the underlying JS library. */
    protected array $options = [];

    /** @var array<string, string|int|float|bool|null> */
    protected array $attributes = [];

    /** @var list<string> */
    protected array $classes = [];

    final public function __construct(?string $id = null)
    {
        if ($id !== null && preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $id) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid component id "%s".', $id));
        }

        $this->id = $id ?? static::name() . '-' . (++self::$sequence);
    }

    public static function make(?string $id = null): static
    {
        return new static($id);
    }

    /**
     * Short name used for `data-celerik` and the component registry (e.g. "grid").
     */
    abstract public static function name(): string;

    /**
     * Options consumed by the JS wrapper.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    abstract public function render(): string;

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Merges raw options for the underlying library (DataTables, Chart.js, Parsley...).
     * They are applied last on the client, so they can override any generated setting.
     *
     * @param array<string, mixed> $options
     */
    public function options(array $options): static
    {
        $this->options = array_replace_recursive($this->options, $options);

        return $this;
    }

    public function attribute(string $name, string|int|float|bool|null $value = true): static
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    public function addClass(string ...$classes): static
    {
        array_push($this->classes, ...$classes);

        return $this;
    }

    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Attributes shared by every component root element.
     *
     * @param list<string> $baseClasses
     * @return array<string, string|int|float|bool|null>
     */
    protected function rootAttributes(array $baseClasses): array
    {
        return array_merge($this->attributes, [
            'id' => $this->id,
            'class' => Html::classes([...$baseClasses, ...$this->classes]),
            'data-celerik' => static::name(),
            'data-celerik-options' => json_encode(
                $this->toArray() === [] ? new \stdClass() : $this->toArray(),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
            ),
        ]);
    }
}
