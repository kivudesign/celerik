<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Theme;

use Celerik\Ui\Theme\Contrast;
use Celerik\Ui\Theme\Theme;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ContrastTest extends TestCase
{
    public function testRatiosMatchKnownWcagValues(): void
    {
        self::assertEqualsWithDelta(21.0, Contrast::ratio('#000000', '#ffffff'), 0.01);
        self::assertEqualsWithDelta(1.0, Contrast::ratio('#777777', '#777777'), 0.001);
        self::assertEqualsWithDelta(4.54, Contrast::ratio('#767676', '#ffffff'), 0.01);
        self::assertSame(Contrast::ratio('#0d6efd', '#ffffff'), Contrast::ratio('#ffffff', '#0d6efd'));
    }

    public function testReportCoversEveryPairInBothModes(): void
    {
        $report = Contrast::report(Theme::preset('material'));

        self::assertCount(2 * count(Contrast::pairs()), $report);
        self::assertSame(['light', 'dark'], array_values(array_unique(array_map(static fn ($r) => $r->mode, $report))));
        self::assertStringStartsWith('PASS light: Body text on #fffbfe = ', (string) $report[0]);
        $border = array_values(array_filter($report, static fn ($r) => $r->foreground === 'inputBorder'))[0];
        self::assertSame(3.0, $border->minimum);
        self::assertSame('1.4.11', $border->wcag);
    }

    public function testThresholdsAreNotRounded(): void
    {
        // #dd3545 on white is 4.497:1: below 4.5:1, and printed truncated (4.49), not rounded.
        $theme = Theme::preset('bootstrap')->color('light', 'danger', '#dd3545');
        $failures = Contrast::failures($theme);

        self::assertCount(1, $failures);
        self::assertSame('danger', $failures[0]->foreground);
        self::assertSame('FAIL light: Error messages on #ffffff = 4.49:1 (needs 4.5:1, WCAG 1.4.3)', (string) $failures[0]);

        $theme->color('light', 'danger', '#dc3545'); // 4.53:1
        self::assertSame([], Contrast::failures($theme));
    }

    public function testFieldBordersNeedThreeToOne(): void
    {
        $theme = Theme::preset('bootstrap')->color('dark', 'inputBorder', '#495057'); // 2.0:1 on #212529

        self::assertSame(['dark inputBorder'], array_map(
            static fn ($r) => $r->mode . ' ' . $r->foreground,
            Contrast::failures($theme),
        ));
    }

    public function testRejectsInvalidColors(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Contrast::ratio('red', '#ffffff');
    }
}
