<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Support;

use Celerik\Ui\Support\Html;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class HtmlTest extends TestCase
{
    public function testEscapesValues(): void
    {
        self::assertSame('&lt;script&gt;&quot;&apos;&amp;', Html::escape('<script>"\'&'));
    }

    public function testRendersBooleanAndSkipsEmptyAttributes(): void
    {
        self::assertSame(
            ' required data-x="&quot;y&quot;"',
            Html::attributes(['required' => true, 'disabled' => false, 'title' => null, 'data-x' => '"y"']),
        );
    }

    public function testRejectsInvalidAttributeNames(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Html::attributes(['onclick="x" a' => 'y']);
    }

    public function testRendersVoidAndNormalElements(): void
    {
        self::assertSame('<input type="text">', Html::tag('input', ['type' => 'text']));
        self::assertSame('<p class="a b">x</p>', Html::tag('p', ['class' => Html::classes(['a', '', 'b', 'a'])], 'x'));
    }
}
