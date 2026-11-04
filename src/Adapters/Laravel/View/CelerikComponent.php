<?php

declare(strict_types=1);

namespace Celerik\Ui\Adapters\Laravel\View;

use Celerik\Ui\Component as UiComponent;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;

/**
 * <x-celerik :component="$grid" />
 */
final class CelerikComponent extends Component
{
    public function __construct(public UiComponent $component)
    {
    }

    public function render(): HtmlString
    {
        return new HtmlString($this->component->render());
    }
}
