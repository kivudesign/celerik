<?php

declare(strict_types=1);

namespace Celerik\Ui\Support;

use LogicException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Server-side cleaning of rich text editor output (Field::editor()).
 *
 * The browser can send any HTML, whatever the editor allows, so sanitize before storing
 * or displaying it. The default configuration allows exactly what the Celerik toolbars
 * produce. Requires symfony/html-sanitizer (`composer require symfony/html-sanitizer`).
 */
final class EditorHtml
{
    /** Elements produced by the Celerik toolbars (besides links). */
    private const ELEMENTS = [
        'p', 'br', 'strong', 'em', 'u', 's', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'pre', 'code',
    ];

    public static function sanitize(string $html, ?HtmlSanitizerConfig $config = null): string
    {
        if (!class_exists(HtmlSanitizer::class)) {
            throw new LogicException(
                'EditorHtml::sanitize() requires symfony/html-sanitizer: composer require symfony/html-sanitizer',
            );
        }

        return (new HtmlSanitizer($config ?? self::config()))->sanitize($html);
    }

    /**
     * Paragraphs, headings (h2-h4), bold, italic, underline, strike, lists, quotes, code,
     * and http(s)/mailto links (forced to rel="noopener noreferrer nofollow").
     */
    public static function config(): HtmlSanitizerConfig
    {
        $config = (new HtmlSanitizerConfig())
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowRelativeLinks()
            ->allowElement('a', ['href', 'target'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
            ->withMaxInputLength(200_000);

        foreach (self::ELEMENTS as $element) {
            $config = $config->allowElement($element);
        }

        return $config;
    }
}
