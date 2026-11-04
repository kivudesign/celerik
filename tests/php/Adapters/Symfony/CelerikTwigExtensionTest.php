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

        self::assertStringContainsString('<script src="/bundles/celerikui/celerik.js" defer></script>', $html);
        self::assertStringContainsString('<table id="users"', $html);
        self::assertStringContainsString('<td>&lt;i&gt;Ada&lt;/i&gt;</td>', $html);
    }
}
