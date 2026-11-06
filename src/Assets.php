<?php

declare(strict_types=1);

namespace Celerik\Ui;

use Celerik\Ui\Support\Html;
use Composer\InstalledVersions;
use InvalidArgumentException;
use LogicException;

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

    /** The bundle of every release on jsDelivr, from the @celerikui/ui npm package. */
    public const CDN = 'https://cdn.jsdelivr.net/npm/@celerikui/ui@%s/public';

    /**
     * The bundle's URL on the jsDelivr CDN, for Assets::tags() when you do not serve the
     * package's public/ folder yourself:
     *
     *     Assets::tags(Assets::cdnUrl());          // the installed version
     *     Assets::tags(Assets::cdnUrl('0.2.0'));
     *
     * Allow https://cdn.jsdelivr.net in script-src and style-src if you use a CSP.
     *
     * @param string|null $version A released version; by default the installed one.
     * @throws InvalidArgumentException When the version is not a release version.
     * @throws LogicException When no version is given and the installed one is a
     *                         development version (a branch or a path repository).
     */
    public static function cdnUrl(?string $version = null): string
    {
        if ($version === null) {
            $installed = class_exists(InstalledVersions::class) && InstalledVersions::isInstalled('celerik/ui')
                ? InstalledVersions::getPrettyVersion('celerik/ui')
                : null;
            if ($installed === null || str_starts_with($installed, 'dev-') || str_ends_with($installed, '-dev')) {
                throw new LogicException(sprintf(
                    'celerik/ui is installed as "%s", which is not on the CDN: pass a released version, '
                    . 'or serve the package\'s public/ folder (npm run build in a development checkout).',
                    $installed ?? 'unknown',
                ));
            }
            $version = $installed;
        }

        $version = ltrim($version, 'v');
        if (preg_match('/^\d+\.\d+\.\d+(-[0-9A-Za-z.]+)?$/', $version) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid version "%s": use a release such as 0.2.0.', $version));
        }

        return sprintf(self::CDN, $version);
    }

    /**
     * @param string|null $nonce CSP nonce. Also printed as <meta name="csp-nonce">, which
     *                           FullCalendar reads to allow the styles it injects at runtime.
     * @param string $theme "bootstrap" (default), "material" or "tailwind".
     * @param string|null $customTheme URL of a stylesheet made with Theme::toCss() (or the
     *                                 ThemeBuilder), loaded last. Its base must be $theme.
     */
    public static function tags(
        string $baseUrl,
        ?string $nonce = null,
        string $theme = 'bootstrap',
        ?string $customTheme = null,
    ): string {
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

        $customLink = $customTheme === null ? '' : "\n" . Html::tag('link', [
            'rel' => 'stylesheet',
            'href' => $customTheme,
        ]);

        return $meta
            . Html::tag('link', ['rel' => 'stylesheet', 'href' => $baseUrl . '/' . self::STYLESHEET])
            . $themeLink
            . $customLink
            . "\n"
            . Html::tag('script', ['type' => 'module', 'src' => $baseUrl . '/' . self::SCRIPT, 'nonce' => $nonce]);
    }
}
