<?php

declare(strict_types=1);

namespace Celerik\Ui\Support;

use Stringable;

/**
 * Trusted HTML, inserted as-is where components accept content (Modal, Tabs).
 * Create it with Html::raw() only for markup you control or have sanitized.
 */
final class RawHtml implements Stringable
{
    public function __construct(private readonly string $html)
    {
    }

    public function __toString(): string
    {
        return $this->html;
    }
}
