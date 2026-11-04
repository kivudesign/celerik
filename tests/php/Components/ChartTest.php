<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Components;

use Celerik\Ui\Components\Chart;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ChartTest extends TestCase
{
    public function testRendersCanvasWithAccessibleNameAndDataTable(): void
    {
        $html = Chart::make('sales')
            ->type('bar')
            ->title('Sales <2026>')
            ->labels(['Jan', 'Feb'])
            ->dataset('Revenue', [10, 20.5])
            ->render();

        self::assertStringContainsString('<figure id="sales"', $html);
        self::assertStringContainsString('<canvas role="img" aria-label="Sales &lt;2026&gt;">', $html);
        self::assertStringContainsString('<table class="visually-hidden"><caption>Sales &lt;2026&gt;</caption>', $html);
        self::assertStringContainsString('<th scope="row">Feb</th><td>20.5</td>', $html);
    }

    public function testBuildsDatasetsFromRows(): void
    {
        $chart = Chart::make()->fromRows(
            [['month' => 'Jan', 'orders' => '3', 'refunds' => 1], ['month' => 'Feb', 'orders' => 5, 'refunds' => null]],
            'month',
            ['orders' => 'Orders', 'refunds' => 'Refunds'],
        );

        self::assertSame([
            'labels' => ['Jan', 'Feb'],
            'datasets' => [
                ['label' => 'Orders', 'data' => [3, 5]],
                ['label' => 'Refunds', 'data' => [1, null]],
            ],
        ], $chart->toData());
    }

    public function testRemoteChartDoesNotEmbedData(): void
    {
        $options = Chart::make()->url('/stats')->height(300)->toArray();

        self::assertSame(['type' => 'line', 'url' => '/stats', 'height' => 300], $options);
    }

    public function testRawOptionsArePassedThrough(): void
    {
        $options = Chart::make()->options(['plugins' => ['legend' => ['position' => 'bottom']]])->toArray();

        self::assertSame('bottom', $options['options']['plugins']['legend']['position']);
    }

    public function testRejectsUnknownType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Chart::make()->type('pyramid');
    }
}
