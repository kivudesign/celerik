<?php

declare(strict_types=1);

namespace App\Twig;

use Celerik\Ui\Theme\Theme;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * The theme saved with the theme builder (CelerikThemeController): config/celerik-theme.json
 * and its stylesheet public/css/celerik-theme.css.
 *
 *     {% set saved = celerik_saved_theme() %}
 *     {{ celerik_assets(asset('bundles/celerikui'), theme: saved.base ?? 'bootstrap', customTheme: saved.url ?? null) }}
 */
final class CelerikThemeExtension extends AbstractExtension
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('celerik_saved_theme', $this->forTemplate(...))];
    }

    public function saved(): ?Theme
    {
        $file = $this->projectDir . '/config/celerik-theme.json';

        return is_file($file) ? Theme::fromJson((string) file_get_contents($file)) : null;
    }

    public function save(Theme $theme): void
    {
        file_put_contents($this->projectDir . '/config/celerik-theme.json', $theme->toJson());
        if (!is_dir($this->projectDir . '/public/css')) {
            mkdir($this->projectDir . '/public/css', 0755, true);
        }
        file_put_contents($this->projectDir . '/public/css/celerik-theme.css', $theme->toCss());
    }

    /** @return array{base: string, url: string}|null */
    public function forTemplate(): ?array
    {
        $theme = $this->saved();

        return $theme === null ? null : ['base' => $theme->getBase(), 'url' => '/css/celerik-theme.css'];
    }
}
