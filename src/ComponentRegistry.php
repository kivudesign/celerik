<?php

declare(strict_types=1);

namespace Celerik\Ui;

use Celerik\Ui\Components\Chart;
use Celerik\Ui\Components\FileManager;
use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Grid;
use Celerik\Ui\Components\Modal;
use Celerik\Ui\Components\Notification;
use Celerik\Ui\Components\Scheduler;
use Celerik\Ui\Components\Tabs;
use InvalidArgumentException;

/**
 * Maps component names to classes. Plugins register their own components here and
 * the matching initializer on the client with `Celerik.register(name, fn)`.
 */
final class ComponentRegistry
{
    /** @var array<string, class-string<Component>> */
    private array $components = [];

    public static function withDefaults(): self
    {
        return (new self())
            ->register(Grid::class)
            ->register(Chart::class)
            ->register(Form::class)
            ->register(Scheduler::class)
            ->register(Modal::class)
            ->register(Tabs::class)
            ->register(Notification::class)
            ->register(FileManager::class);
    }

    /**
     * @param class-string<Component> $class
     */
    public function register(string $class): self
    {
        if (!is_subclass_of($class, Component::class)) {
            throw new InvalidArgumentException(sprintf('%s must extend %s.', $class, Component::class));
        }

        $this->components[$class::name()] = $class;

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->components[$name]);
    }

    public function create(string $name, ?string $id = null): Component
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException(sprintf('Unknown component "%s".', $name));
        }

        return $this->components[$name]::make($id);
    }

    /**
     * @return array<string, class-string<Component>>
     */
    public function all(): array
    {
        return $this->components;
    }
}
