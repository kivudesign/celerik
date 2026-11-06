<?php

declare(strict_types=1);

namespace Celerik\Ui\Adapters\Symfony;

use Celerik\Ui\Assets;
use Celerik\Ui\Component;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * {{ celerik_assets() }} or {{ celerik_assets(theme: 'material', customTheme: '/css/acme.css') }}
 * — load jQuery and Bootstrap before it.
 * {{ celerik(grid) }}    — renders any Celerik component.
 */
final class CelerikTwigExtension extends AbstractExtension
{
    public function __construct(private readonly string $assetsBaseUrl = '/bundles/celerikui')
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('celerik', $this->render(...), ['is_safe' => ['html']]),
            new TwigFunction('celerik_assets', $this->assets(...), ['is_safe' => ['html']]),
        ];
    }

    public function render(Component $component): string
    {
        return $component->render();
    }

    public function assets(
        ?string $baseUrl = null,
        ?string $nonce = null,
        string $theme = 'bootstrap',
        ?string $customTheme = null,
    ): string {
        return Assets::tags($baseUrl ?? $this->assetsBaseUrl, $nonce, $theme, $customTheme);
    }
}
