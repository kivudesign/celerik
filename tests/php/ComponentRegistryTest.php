<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests;

use Celerik\Ui\Assets;
use Celerik\Ui\ComponentRegistry;
use Celerik\Ui\Components\Grid;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ComponentRegistryTest extends TestCase
{
    public function testCreatesRegisteredComponents(): void
    {
        $registry = ComponentRegistry::withDefaults();

        self::assertSame(['grid', 'chart', 'form', 'scheduler', 'modal', 'tabs', 'notification', 'filemanager'], array_keys($registry->all()));
        self::assertInstanceOf(Grid::class, $registry->create('grid', 'orders'));
        self::assertSame('orders', $registry->create('grid', 'orders')->getId());
    }

    public function testRejectsUnknownNamesAndNonComponents(): void
    {
        $registry = new ComponentRegistry();

        try {
            $registry->create('gantt');
            self::fail('Expected an exception for an unknown component.');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(InvalidArgumentException::class);
        /** @phpstan-ignore argument.type */
        $registry->register(stdClass::class);
    }

    public function testRejectsInvalidIds(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Grid::make('"><img src=x>');
    }

    public function testAssetTags(): void
    {
        self::assertSame(
            '<link rel="stylesheet" href="/vendor/celerik/celerik.css">' . "\n"
            . '<script type="module" src="/vendor/celerik/celerik.js"></script>',
            Assets::tags('/vendor/celerik/'),
        );
    }

    public function testAssetTagsAddTheThemeStylesheetAfterCelerikCss(): void
    {
        self::assertSame(
            '<link rel="stylesheet" href="/assets/celerik.css">' . "\n"
            . '<link rel="stylesheet" href="/assets/celerik-material.css">' . "\n"
            . '<script type="module" src="/assets/celerik.js"></script>',
            Assets::tags('/assets', theme: 'material'),
        );
        self::assertStringContainsString('celerik-tailwind.css', Assets::tags('/assets', theme: 'tailwind'));
        self::assertSame(Assets::tags('/assets'), Assets::tags('/assets', theme: 'bootstrap'));

        $this->expectException(InvalidArgumentException::class);
        Assets::tags('/assets', theme: '../../evil');
    }

    public function testAssetTagsWithNonceIncludeCspNonceMetaForFullCalendar(): void
    {
        self::assertSame(
            '<meta name="csp-nonce" content="abc">' . "\n"
            . '<link rel="stylesheet" href="/vendor/celerik/celerik.css">' . "\n"
            . '<script type="module" src="/vendor/celerik/celerik.js" nonce="abc"></script>',
            Assets::tags('/vendor/celerik/', 'abc'),
        );
    }
}
