<?php

declare(strict_types=1);

namespace Celerik\Ui\Adapters\Symfony;

use Celerik\Ui\ComponentRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Register in config/bundles.php:
 *     Celerik\Ui\Adapters\Symfony\CelerikUiBundle::class => ['all' => true],
 *
 * Twig: {{ celerik_assets() }} and {{ celerik(grid) }}
 * Assets: php bin/console assets:install (copies public/ to public/bundles/celerikui)
 */
final class CelerikUiBundle extends AbstractBundle
{
    /**
     * The package root, so that assets:install finds the prebuilt public/ directory.
     */
    public function getPath(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        $services->set(ComponentRegistry::class)
            ->factory([ComponentRegistry::class, 'withDefaults']);

        $services->set(CelerikTwigExtension::class)
            ->tag('twig.extension');
    }
}
