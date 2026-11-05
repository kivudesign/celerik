<?php

declare(strict_types=1);

namespace Celerik\Ui\Components;

use Celerik\Ui\Component;
use Celerik\Ui\Support\Html;
use InvalidArgumentException;
use LogicException;

/**
 * File manager (elFinder). The connector endpoint runs FileManagerConnector::run().
 *
 * elFinder's own JS and CSS are loaded from assetsUrl() (copy them from
 * vendor/studio-42/elfinder: css, img, js, sounds); jQuery UI comes with the Celerik
 * chunk, which is loaded only on pages with a file manager.
 */
final class FileManager extends Component
{
    private ?string $url = null;

    private string $assetsUrl = '/elfinder';

    private int $height = 500;

    private string $lang = 'en';

    private string $title = 'File manager';

    private ?string $csrfToken = null;

    private string $csrfHeader = 'X-CSRF-TOKEN';

    public static function name(): string
    {
        return 'filemanager';
    }

    /**
     * The connector endpoint (it must accept GET and POST).
     */
    public function url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Base URL of elFinder's assets (css/, img/, js/, sounds/), default "/elfinder".
     */
    public function assetsUrl(string $url): self
    {
        $this->assetsUrl = rtrim($url, '/');

        return $this;
    }

    public function height(int $height): self
    {
        if ($height < 200) {
            throw new InvalidArgumentException('The file manager needs at least 200 px.');
        }

        $this->height = $height;

        return $this;
    }

    /**
     * Interface language, e.g. "fr" (loaded from assetsUrl()/js/i18n/).
     */
    public function lang(string $lang): self
    {
        if (preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $lang) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid language "%s".', $lang));
        }

        $this->lang = $lang;

        return $this;
    }

    /**
     * Accessible name of the file manager region.
     */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * The framework's CSRF token, sent as a header with every request (Laravel reads
     * X-CSRF-TOKEN natively). elFinder adds its own token on top.
     */
    public function csrf(string $token, string $header = 'X-CSRF-TOKEN'): self
    {
        if (preg_match('/^[A-Za-z0-9-]+$/', $header) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid header name "%s".', $header));
        }

        $this->csrfToken = $token;
        $this->csrfHeader = $header;

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url,
            'assetsUrl' => $this->assetsUrl,
            'height' => $this->height,
            'lang' => $this->lang,
            'csrf' => $this->csrfToken === null ? null : ['header' => $this->csrfHeader, 'token' => $this->csrfToken],
            'options' => $this->options,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    public function render(): string
    {
        if ($this->url === null) {
            throw new LogicException(sprintf('File manager "%s" has no connector url().', $this->id));
        }

        return Html::tag('div', array_merge($this->rootAttributes(['celerik-filemanager']), [
            'role' => 'region',
            'aria-label' => $this->title,
        ]), Html::tag('noscript', [], Html::tag('p', [], 'JavaScript is required to use the file manager.')));
    }
}
