<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Adapters\Symfony;

use Celerik\Ui\Adapters\Symfony\CelerikTwigExtension;
use Celerik\Ui\Components\Grid;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class CelerikTwigExtensionTest extends TestCase
{
    public function testRendersComponentsAndAssetsWithoutDoubleEscaping(): void
    {
        $twig = new Environment(new ArrayLoader([
            'page' => '{{ celerik_assets() }}{{ celerik(grid) }}',
        ]), ['autoescape' => 'html']);
        $twig->addExtension(new CelerikTwigExtension());

        $html = $twig->render('page', ['grid' => Grid::make('users')->column('name')->rows([['name' => '<i>Ada</i>']])]);

        self::assertStringContainsString('<script type="module" src="/bundles/celerikui/celerik.js"></script>', $html);
        self::assertStringContainsString('<table id="users"', $html);
        self::assertStringContainsString('<td>&lt;i&gt;Ada&lt;/i&gt;</td>', $html);
    }

    public function testAssetsFunctionAcceptsATheme(): void
    {
        $twig = new Environment(new ArrayLoader(['page' => "{{ celerik_assets(theme: 'tailwind') }}"]));
        $twig->addExtension(new CelerikTwigExtension());

        self::assertStringContainsString('<link rel="stylesheet" href="/bundles/celerikui/celerik-tailwind.css">', $twig->render('page'));
    }

    public function testAssetsFunctionAcceptsACustomTheme(): void
    {
        $twig = new Environment(new ArrayLoader([
            'page' => "{{ celerik_assets(theme: 'material', customTheme: '/css/acme.css') }}",
        ]));
        $twig->addExtension(new CelerikTwigExtension());

        self::assertStringContainsString(
            'celerik-material.css">' . "\n" . '<link rel="stylesheet" href="/css/acme.css">',
            $twig->render('page'),
        );
    }
}
