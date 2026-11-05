<?php

declare(strict_types=1);

namespace Celerik\Ui\Components;

use Celerik\Ui\Component;
use Celerik\Ui\Support\Html;
use InvalidArgumentException;

/**
 * Toast notifications (Notyf), typically from flash messages after a redirect.
 *
 * Messages are always treated as text, never HTML. Errors stay until dismissed by
 * default, so they cannot disappear before being read. Without JavaScript the messages
 * are shown as Bootstrap alerts.
 */
final class Notification extends Component
{
    public const TYPES = ['success', 'error', 'info', 'warning'];

    /** Flash keys commonly used by frameworks, mapped to notification types. */
    private const FLASH_TYPES = [
        'success' => 'success', 'status' => 'success', 'message' => 'info', 'notice' => 'info',
        'info' => 'info', 'warning' => 'warning', 'error' => 'error', 'danger' => 'error',
    ];

    private const ALERT_CLASSES = [
        'success' => 'success', 'error' => 'danger', 'info' => 'info', 'warning' => 'warning',
    ];

    /** @var list<array{type: string, message: string}> */
    private array $messages = [];

    private string $x = 'right';

    private string $y = 'top';

    private int $duration = 5000;

    private int $errorDuration = 0;

    private bool $dismissible = true;

    private ?string $dismissLabel = null;

    public static function name(): string
    {
        return 'notification';
    }

    public function add(string $type, string $message): self
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown notification type "%s". Supported: %s.',
                $type,
                implode(', ', self::TYPES),
            ));
        }

        if (trim($message) !== '') {
            $this->messages[] = ['type' => $type, 'message' => $message];
        }

        return $this;
    }

    public function success(string $message): self
    {
        return $this->add('success', $message);
    }

    public function error(string $message): self
    {
        return $this->add('error', $message);
    }

    public function info(string $message): self
    {
        return $this->add('info', $message);
    }

    public function warning(string $message): self
    {
        return $this->add('warning', $message);
    }

    /**
     * Adds flash messages: key => message or list of messages. Common keys are mapped
     * (status → success, danger → error, notice → info...); unknown keys are ignored.
     *
     * Laravel:  ->fromFlash(session()->only(['success', 'status', 'error', 'warning', 'info']))
     * Symfony:  ->fromFlash($request->getSession()->getFlashBag()->all())
     *
     * @param array<string, mixed> $flash
     */
    public function fromFlash(array $flash): self
    {
        foreach ($flash as $key => $messages) {
            $type = self::FLASH_TYPES[strtolower((string) $key)] ?? null;
            if ($type === null) {
                continue;
            }

            foreach ((array) $messages as $message) {
                if (is_string($message)) {
                    $this->add($type, $message);
                }
            }
        }

        return $this;
    }

    /**
     * Screen position: x "left", "center" or "right"; y "top" or "bottom".
     */
    public function position(string $x, string $y): self
    {
        if (!in_array($x, ['left', 'center', 'right'], true) || !in_array($y, ['top', 'bottom'], true)) {
            throw new InvalidArgumentException(sprintf('Invalid position "%s %s".', $x, $y));
        }

        $this->x = $x;
        $this->y = $y;

        return $this;
    }

    /**
     * Display time in milliseconds for success, info and warning messages.
     */
    public function duration(int $milliseconds): self
    {
        $this->duration = max(0, $milliseconds);

        return $this;
    }

    /**
     * Display time for errors; 0 (default) keeps them until dismissed.
     */
    public function errorDuration(int $milliseconds): self
    {
        $this->errorDuration = max(0, $milliseconds);

        return $this;
    }

    public function dismissible(bool $dismissible = true): self
    {
        $this->dismissible = $dismissible;

        return $this;
    }

    /**
     * Accessible name of the dismiss buttons (default "Dismiss notification").
     */
    public function dismissLabel(string $label): self
    {
        $this->dismissLabel = $label;

        return $this;
    }

    /**
     * @return list<array{type: string, message: string}>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function toArray(): array
    {
        return array_filter([
            'messages' => $this->messages,
            'position' => ['x' => $this->x, 'y' => $this->y],
            'duration' => $this->duration,
            'errorDuration' => $this->errorDuration,
            'dismissible' => $this->dismissible,
            'labels' => $this->dismissLabel === null ? null : ['dismiss' => $this->dismissLabel],
            'options' => $this->options,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    public function render(): string
    {
        $alerts = '';
        foreach ($this->messages as $message) {
            $alerts .= Html::tag('div', [
                'class' => 'alert alert-' . self::ALERT_CLASSES[$message['type']],
                'role' => $message['type'] === 'error' ? 'alert' : 'status',
            ], Html::escape($message['message']));
        }

        return Html::tag(
            'div',
            $this->rootAttributes(['celerik-notification']),
            $alerts === '' ? '' : Html::tag('noscript', [], $alerts),
        );
    }
}
