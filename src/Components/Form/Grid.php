<?php

declare(strict_types=1);

namespace Celerik\Ui\Components\Form;

use Celerik\Ui\Support\Html;
use Closure;
use InvalidArgumentException;

/**
 * Lays out the fields and sections of a form or section in Bootstrap grid columns.
 *
 * @internal
 */
final class Grid
{
    /** Divisors of Bootstrap's 12 columns. */
    public const COLUMNS = [1, 2, 3, 4, 6];

    public const BREAKPOINTS = ['sm', 'md', 'lg', 'xl', 'xxl'];

    public static function assertColumns(int $columns, string $breakpoint): void
    {
        if (!in_array($columns, self::COLUMNS, true)) {
            throw new InvalidArgumentException(sprintf('A form has 1, 2, 3, 4 or 6 columns, not %d.', $columns));
        }

        if (!in_array($breakpoint, self::BREAKPOINTS, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown breakpoint "%s": use %s.',
                $breakpoint,
                implode(', ', self::BREAKPOINTS),
            ));
        }
    }

    public static function assertSpan(int|string $span): void
    {
        if ($span !== 'full' && (!is_int($span) || $span < 1)) {
            throw new InvalidArgumentException('A column span is a number of columns from 1, or "full".');
        }
    }

    /**
     * @param list<Field|Section|Repeater>            $items
     * @param Closure(Field|Section|Repeater): string $render
     */
    public static function render(array $items, int $columns, string $breakpoint, Closure $render): string
    {
        $html = '';
        foreach ($items as $item) {
            $content = $render($item);
            // Hidden inputs take no column; one column needs no grid.
            if ($columns === 1 || ($item instanceof Field && $item->getType() === 'hidden')) {
                $html .= $content;
                continue;
            }

            $span = $item->getColumnSpan() ?? 1;
            $span = $span === 'full' ? $columns : min($span, $columns);
            $width = intdiv(12 * $span, $columns);
            $html .= Html::tag('div', ['class' => 'col-12 col-' . $breakpoint . '-' . $width], $content);
        }

        return $columns === 1 ? $html : Html::tag('div', ['class' => 'row'], $html);
    }
}
