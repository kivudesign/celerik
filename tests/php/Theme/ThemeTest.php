<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Theme;

use Celerik\Ui\Theme\Contrast;
use Celerik\Ui\Theme\Theme;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ThemeTest extends TestCase
{
    #[DataProvider('bases')]
    public function testPresetsRoundTripAndMeetContrast(string $base): void
    {
        $theme = Theme::preset($base);

        self::assertSame($base, $theme->getBase());
        self::assertSame(
            json_decode((string) file_get_contents(Theme::presetPath($base)), true),
            $theme->toArray(),
        );
        self::assertEquals($theme, Theme::fromJson($theme->toJson()));
        self::assertSame([], array_map('strval', Contrast::failures($theme)));
    }

    /** @return iterable<string, array{string}> */
    public static function bases(): iterable
    {
        foreach (Theme::BASES as $base) {
            yield $base => [$base];
        }
    }

    public function testFluentChangesAreValidatedAndNormalized(): void
    {
        $theme = Theme::preset('tailwind')
            ->name('Acme Café')
            ->base('bootstrap')
            ->color('light', 'primary', '#0B5394')
            ->radius(0.25)
            ->fontFamily('"Inter", system-ui, sans-serif')
            ->shadow('subtle');

        self::assertSame('Acme Café', $theme->getName());
        self::assertSame('#0b5394', $theme->palette('light')['primary']);
        self::assertSame('11, 83, 148', $theme->rgb('light', 'primary'));
        self::assertSame(0.25, $theme->getRadius());
        self::assertSame('"Inter", system-ui, sans-serif', $theme->getFontFamily());
        self::assertSame('subtle', $theme->getShadow());

        $theme->radius(null)->fontFamily(null)->shadow(null);
        self::assertSame(['name', 'base', 'light', 'dark'], array_keys($theme->toArray()));
    }

    /** @param callable(Theme): mixed $change */
    #[DataProvider('invalidChanges')]
    public function testRejectsInvalidValues(callable $change): void
    {
        $this->expectException(InvalidArgumentException::class);

        $change(Theme::preset('bootstrap'));
    }

    /** @return iterable<string, array{callable(Theme): mixed}> */
    public static function invalidChanges(): iterable
    {
        yield 'named color' => [static fn (Theme $t) => $t->color('light', 'primary', 'red')];
        yield 'short hex' => [static fn (Theme $t) => $t->color('light', 'primary', '#fff')];
        yield 'css injection in a color' => [static fn (Theme $t) => $t->color('light', 'primary', '#ffffff;}body{display:none')];
        yield 'unknown token' => [static fn (Theme $t) => $t->color('light', 'accent', '#ffffff')];
        yield 'unknown mode' => [static fn (Theme $t) => $t->color('dim', 'primary', '#ffffff')];
        yield 'font injection' => [static fn (Theme $t) => $t->fontFamily('Inter; } body { display: none')];
        yield 'font url' => [static fn (Theme $t) => $t->fontFamily('url(https://evil.example/f.woff)')];
        yield 'radius too large' => [static fn (Theme $t) => $t->radius(3.0)];
        yield 'negative radius' => [static fn (Theme $t) => $t->radius(-1.0)];
        yield 'unknown shadow' => [static fn (Theme $t) => $t->shadow('glow')];
        yield 'unknown base' => [static fn (Theme $t) => $t->base('bulma')];
        yield 'markup in the name' => [static fn (Theme $t) => $t->name('<script>')];
        yield 'empty name' => [static fn (Theme $t) => $t->name('')];
        yield 'unknown preset' => [static fn () => Theme::preset('../../composer')];
    }

    /** @param array<mixed> $data */
    #[DataProvider('invalidArrays')]
    public function testFromArrayRejectsMalformedThemes(array $data): void
    {
        $this->expectException(InvalidArgumentException::class);

        Theme::fromArray($data);
    }

    /** @return iterable<string, array{array<mixed>}> */
    public static function invalidArrays(): iterable
    {
        $valid = Theme::preset('material')->toArray();

        yield 'unknown key' => [$valid + ['css' => 'body{}']];
        yield 'missing palette' => [array_diff_key($valid, ['dark' => true])];
        yield 'missing color' => [['light' => array_diff_key($valid['light'], ['danger' => true])] + $valid];
        yield 'extra color' => [['light' => $valid['light'] + ['accent' => '#000000']] + $valid];
        yield 'color as a number' => [['light' => ['primary' => 123] + $valid['light']] + $valid];
        yield 'palette as a string' => [['dark' => '#000000'] + $valid];
        yield 'radius as a string' => [['radius' => '1rem'] + $valid];
        yield 'shadow as an array' => [['shadow' => ['none']] + $valid];
    }

    public function testFromJsonRejectsInvalidJson(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid theme JSON');

        Theme::fromJson('{"name": ');
    }

    public function testFromJsonRejectsNonObjects(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Theme::fromJson('"material"');
    }
}
