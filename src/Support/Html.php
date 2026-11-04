<?php

declare(strict_types=1);

namespace Celerik\Ui\Support;

use InvalidArgumentException;

/**
 * Minimal, escaping-by-default HTML builder used by every component.
 */
final class Html
{
    private const VOID_ELEMENTS = ['input', 'img', 'br', 'hr', 'meta', 'link', 'source', 'col'];

    public static function escape(string|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /**
     * Renders an attribute list. `true` renders a boolean attribute, `null` and `false` skip it.
     *
     * @param array<string, string|int|float|bool|null> $attributes
     */
    public static function attributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if (preg_match('/^[a-zA-Z_:][a-zA-Z0-9_:.-]*$/', $name) !== 1) {
                throw new InvalidArgumentException(sprintf('Invalid HTML attribute name "%s".', $name));
            }

            $html .= $value === true ? ' ' . $name : ' ' . $name . '="' . self::escape($value) . '"';
        }

        return $html;
    }

    /**
     * Renders an element. `$content` is inserted as-is: escape it before passing user data.
     *
     * @param array<string, string|int|float|bool|null> $attributes
     */
    public static function tag(string $name, array $attributes = [], ?string $content = null): string
    {
        $open = '<' . $name . self::attributes($attributes) . '>';

        if (in_array($name, self::VOID_ELEMENTS, true)) {
            return $open;
        }

        return $open . ($content ?? '') . '</' . $name . '>';
    }

    /**
     * Joins CSS classes, dropping empty entries and duplicates.
     *
     * @param list<string|null> $classes
     */
    public static function classes(array $classes): ?string
    {
        $classes = array_unique(array_filter(
            $classes,
            static fn (?string $class): bool => $class !== null && $class !== '',
        ));

        return $classes === [] ? null : implode(' ', $classes);
    }
}
