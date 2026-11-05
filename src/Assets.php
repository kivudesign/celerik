<?php

declare(strict_types=1);

namespace Celerik\Ui;

use Celerik\Ui\Support\Html;
use InvalidArgumentException;

/**
 * Renders the tags for the prebuilt bundle (public/celerik.css and public/celerik.js).
 *
 * celerik.js is an ES module that loads each component's library from public/chunks/
 * only when that component is on the page. Module scripts are deferred, so jQuery and
 * Bootstrap (peer dependencies) only need to be included before these tags.
 */
final class Assets
{
    public const STYLESHEET = 'celerik.css';
    public const SCRIPT = 'celerik.js';

    /** "bootstrap" is the default look; the others add a stylesheet after celerik.css. */
    public const THEMES = ['bootstrap', 'material', 'tailwind'];

    /**
     * @param string|null $nonce CSP nonce. Also printed as <meta name="csp-nonce">, which
     *                           FullCalendar reads to allow the styles it injects at runtime.
     * @param string $theme "bootstrap" (default), "material" or "tailwind".
     */
    public static function tags(string $baseUrl, ?string $nonce = null, string $theme = 'bootstrap'): string
    {
        if (!in_array($theme, self::THEMES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown theme "%s". Available: %s.',
                $theme,
                implode(', ', self::THEMES),
            ));
        }

        $baseUrl = rtrim($baseUrl, '/');
        $meta = $nonce === null ? '' : Html::tag('meta', ['name' => 'csp-nonce', 'content' => $nonce]) . "\n";
        $themeLink = $theme === 'bootstrap' ? '' : "\n" . Html::tag('link', [
            'rel' => 'stylesheet',
            'href' => $baseUrl . '/celerik-' . $theme . '.css',
        ]);

        return $meta
            . Html::tag('link', ['rel' => 'stylesheet', 'href' => $baseUrl . '/' . self::STYLESHEET])
            . $themeLink
            . "\n"
            . Html::tag('script', ['type' => 'module', 'src' => $baseUrl . '/' . self::SCRIPT, 'nonce' => $nonce]);
    }
}
