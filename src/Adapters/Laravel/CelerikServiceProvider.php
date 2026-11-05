<?php

declare(strict_types=1);

namespace Celerik\Ui\Adapters\Laravel;

use Celerik\Ui\Adapters\Laravel\View\AssetsComponent;
use Celerik\Ui\Adapters\Laravel\View\CelerikComponent;
use Celerik\Ui\ComponentRegistry;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

/**
 * Auto-discovered through `extra.laravel.providers` in composer.json.
 *
 * Blade: <x-celerik-assets /> and <x-celerik :component="$grid" />
 * Assets: php artisan vendor:publish --tag=celerik-assets
 * elFinder assets (File Manager): php artisan vendor:publish --tag=celerik-elfinder
 */
final class CelerikServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ComponentRegistry::class,
            static fn (): ComponentRegistry => ComponentRegistry::withDefaults(),
        );
    }

    public function boot(): void
    {
        $this->publishes([
            dirname(__DIR__, 3) . '/public' => $this->app->publicPath('vendor/celerik'),
        ], 'celerik-assets');

        // elFinder's own JS, CSS, images and sounds, when studio-42/elfinder is installed.
        $elfinder = $this->app->basePath('vendor/studio-42/elfinder');
        if (is_dir($elfinder)) {
            $paths = [];
            foreach (['css', 'img', 'js', 'sounds'] as $directory) {
                $paths[$elfinder . '/' . $directory] = $this->app->publicPath('vendor/elfinder/' . $directory);
            }
            $this->publishes($paths, 'celerik-elfinder');
        }

        $this->callAfterResolving(BladeCompiler::class, static function (BladeCompiler $blade): void {
            $blade->component('celerik', CelerikComponent::class);
            $blade->component('celerik-assets', AssetsComponent::class);
        });
    }
}
