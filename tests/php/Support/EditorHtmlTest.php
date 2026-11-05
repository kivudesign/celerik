<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Support;

use Celerik\Ui\Support\EditorHtml;
use PHPUnit\Framework\TestCase;

final class EditorHtmlTest extends TestCase
{
    public function testKeepsWhatTheToolbarsProduce(): void
    {
        $html = '<h2>Title</h2><p><strong>Bold</strong>, <em>italic</em>, <u>under</u>, <s>strike</s></p>'
            . '<ul><li>one</li></ul><ol><li>two</li></ol><blockquote>quote</blockquote><pre>code</pre>';

        self::assertSame($html, EditorHtml::sanitize($html));
    }

    public function testRemovesScriptsHandlersAndDangerousLinks(): void
    {
        $clean = EditorHtml::sanitize(
            '<p onclick="steal()">Hi<script>alert(1)</script><img src=x onerror=alert(1)></p>'
            . '<a href="javascript:alert(1)">bad</a><a href="https://example.com" target="_blank">ok</a>'
            . '<iframe src="https://evil.example"></iframe><p style="position:fixed">styled</p>',
        );

        self::assertStringNotContainsString('script', $clean);
        self::assertStringNotContainsString('onclick', $clean);
        self::assertStringNotContainsString('onerror', $clean);
        self::assertStringNotContainsString('javascript:', $clean);
        self::assertStringNotContainsString('iframe', $clean);
        self::assertStringNotContainsString('style=', $clean);
        self::assertStringContainsString('<a href="https://example.com" target="_blank" rel="noopener noreferrer nofollow">ok</a>', $clean);
        self::assertStringContainsString('<p>Hi</p>', $clean);
    }
}
