<?php

declare(strict_types=1);

namespace Celerik\Ui;

use Celerik\Ui\Support\Html;

/**
 * Renders the tags for the prebuilt bundle (public/celerik.css and public/celerik.js).
 *
 * jQuery and Bootstrap are peer dependencies: load them before these tags.
 */
final class Assets
{
    public const STYLESHEET = 'celerik.css';
    public const SCRIPT = 'celerik.js';

    public static function tags(string $baseUrl, ?string $nonce = null): string
    {
        $baseUrl = rtrim($baseUrl, '/');

        return Html::tag('link', ['rel' => 'stylesheet', 'href' => $baseUrl . '/' . self::STYLESHEET])
            . "\n"
            . Html::tag('script', ['src' => $baseUrl . '/' . self::SCRIPT, 'defer' => true, 'nonce' => $nonce]);
    }
}
