<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests;

use Celerik\Ui\Assets;
use Celerik\Ui\ComponentRegistry;
use Celerik\Ui\Components\Grid;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ComponentRegistryTest extends TestCase
{
    public function testCreatesRegisteredComponents(): void
    {
        $registry = ComponentRegistry::withDefaults();

        self::assertSame(['grid', 'chart', 'form', 'scheduler', 'modal', 'tabs', 'notification', 'filemanager', 'themebuilder'], array_keys($registry->all()));
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

    public function testAssetTagsLoadACustomThemeLastAndEscapeItsUrl(): void
    {
        self::assertSame(
            '<link rel="stylesheet" href="/assets/celerik.css">' . "\n"
            . '<link rel="stylesheet" href="/assets/celerik-tailwind.css">' . "\n"
            . '<link rel="stylesheet" href="/css/acme.css?v=2&amp;x=&quot;">' . "\n"
            . '<script type="module" src="/assets/celerik.js"></script>',
            Assets::tags('/assets', theme: 'tailwind', customTheme: '/css/acme.css?v=2&x="'),
        );
    }

    public function testCdnUrlPointsToAReleaseOnJsDelivr(): void
    {
        self::assertSame('https://cdn.jsdelivr.net/npm/@celerikui/ui@0.2.0/public', Assets::cdnUrl('0.2.0'));
        self::assertSame('https://cdn.jsdelivr.net/npm/@celerikui/ui@1.0.0-beta.1/public', Assets::cdnUrl('v1.0.0-beta.1'));
        self::assertStringContainsString(
            '<script type="module" src="https://cdn.jsdelivr.net/npm/@celerikui/ui@0.2.0/public/celerik.js"></script>',
            Assets::tags(Assets::cdnUrl('0.2.0')),
        );
    }

    public function testCdnUrlRejectsVersionsThatAreNotReleases(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Assets::cdnUrl('latest/../../evil');
    }

    public function testCdnUrlNeedsAVersionForADevelopmentInstall(): void
    {
        // In this repository celerik/ui is the root package, installed as a branch (dev-…).
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('which is not on the CDN');

        Assets::cdnUrl();
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
