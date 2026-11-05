<?php

declare(strict_types=1);

namespace Celerik\Ui\FileManager;

use Celerik\Ui\Upload\UploadedFile;
use Celerik\Ui\Upload\UploadException;
use Celerik\Ui\Upload\UploadHandler;
use Celerik\Ui\Upload\UploadRules;
use InvalidArgumentException;

/**
 * A locked-down elFinder connector configuration for one local folder.
 *
 * - Files have no public URL: they are served through the connector, so keep the root
 *   outside the web root and protect the connector route with authorization.
 * - Uploads are checked like UploadHandler does: real MIME type, extension matching the
 *   content, no executable or script formats, no double extensions.
 * - New names (upload, mkdir, rename) can never be dotfiles or use a forbidden extension.
 * - Dotfiles (.htaccess, elFinder's .tmb thumbnails...) are hidden and inaccessible.
 * - Commands that extract archives, mount remote storage, change permissions or edit
 *   file contents are disabled; SVG and HTML are never displayed inline.
 */
final class FileManagerConfig
{
    /** Never enabled: archive extraction, remote mounts and permission changes. */
    public const ALWAYS_DISABLED = ['archive', 'extract', 'netmount', 'chmod'];

    /** Disabled unless allow() is called: creating/editing text files, zip downloads, image resizing. */
    public const OPTIONAL_COMMANDS = ['mkfile', 'put', 'zipdl', 'resize'];

    /** Commands that change files, refused in read-only mode. */
    public const WRITE_COMMANDS = ['mkdir', 'mkfile', 'rm', 'rename', 'duplicate', 'paste', 'upload', 'put', 'resize'];

    /** Only these types are displayed in the browser; everything else is downloaded. */
    private const INLINE_TYPES = '^(?:image/(?:jpeg|png|gif|webp|avif)|video/|audio/|application/pdf$|text/plain$)';

    private string $alias;

    private bool $readOnly = false;

    /** @var list<string> */
    private array $allowed = [];

    private function __construct(
        private readonly string $root,
        private readonly UploadRules $rules,
    ) {
        $this->alias = basename($root);
    }

    /**
     * @param string $root Existing directory, ideally outside the web root.
     * @param UploadRules|null $rules Accepted uploads (defaults to images and documents up to 5 MB).
     */
    public static function local(string $root, ?UploadRules $rules = null): self
    {
        $path = realpath($root);

        if ($path === false || !is_dir($path)) {
            throw new InvalidArgumentException(sprintf('The file manager root "%s" is not a directory.', $root));
        }

        $rules ??= UploadRules::images()
            ->withType('application/pdf', ['pdf'])
            ->withType('text/plain', ['txt']);

        return new self($path, $rules);
    }

    /**
     * Name of the root folder shown to users.
     */
    public function alias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Browse and download only: every command that changes files is disabled.
     */
    public function readOnly(bool $readOnly = true): self
    {
        $this->readOnly = $readOnly;

        return $this;
    }

    /**
     * Enables optional commands (mkfile, put, zipdl, resize). Archive, extract, netmount and
     * chmod can never be enabled.
     */
    public function allow(string ...$commands): self
    {
        foreach ($commands as $command) {
            if (!in_array($command, self::OPTIONAL_COMMANDS, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Command "%s" cannot be enabled. Optional commands: %s.',
                    $command,
                    implode(', ', self::OPTIONAL_COMMANDS),
                ));
            }
            $this->allowed[] = $command;
        }

        return $this;
    }

    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * @return list<string>
     */
    public function disabledCommands(): array
    {
        $disabled = [...self::ALWAYS_DISABLED, ...array_diff(self::OPTIONAL_COMMANDS, $this->allowed)];

        if ($this->readOnly) {
            $disabled = [...$disabled, ...self::WRITE_COMMANDS];
        }

        return array_values(array_unique($disabled));
    }

    /**
     * Whether a new file or folder name is acceptable (elFinder's acceptedName).
     */
    public static function isAcceptedName(string $name): bool
    {
        if ($name === '' || mb_strlen($name) > 255 || str_starts_with($name, '.')) {
            return false;
        }

        if (preg_match('~[/\\\\\x00-\x1F\x7F:*?"<>|]~u', $name) === 1) {
            return false;
        }

        foreach (array_slice(explode('.', strtolower($name)), 1) as $segment) {
            if (in_array($segment, UploadRules::FORBIDDEN_EXTENSIONS, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * elFinder access control: dotfiles are hidden and can be neither read nor written.
     *
     * @return bool|null null lets elFinder apply its defaults.
     */
    public static function access(string $attribute, string $path): ?bool
    {
        if (str_starts_with(basename($path), '.')) {
            return $attribute === 'hidden' || $attribute === 'locked';
        }

        return null;
    }

    /**
     * The elFinder connector options.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $inspector = new UploadHandler($this->rules, sys_get_temp_dir());

        return [
            'debug' => false,
            'bind' => [
                // Same checks as UploadHandler: content-based MIME type, matching extension...
                'upload.presave' => [
                    static function (&$targetHash, &$name, $temporaryPath) use ($inspector): bool|array {
                        try {
                            $inspector->inspect(new UploadedFile((string) $temporaryPath, (string) $name));
                        } catch (UploadException $e) {
                            return ['error' => $e->getMessage()];
                        }

                        return true;
                    },
                ],
            ],
            'roots' => [[
                'driver' => 'LocalFileSystem',
                'path' => $this->root,
                'URL' => '',
                'alias' => $this->alias,
                'mimeDetect' => 'finfo',
                'uploadAllow' => array_keys($this->rules->types),
                'uploadDeny' => ['all'],
                'uploadOrder' => ['deny', 'allow'],
                'uploadMaxSize' => $this->rules->maxSize,
                'uploadOverwrite' => false,
                'acceptedName' => [self::class, 'isAcceptedName'],
                'accessControl' => [self::class, 'access'],
                'disabled' => $this->disabledCommands(),
                'defaults' => ['read' => true, 'write' => !$this->readOnly],
                'dispInlineRegex' => self::INLINE_TYPES,
                'tmbPath' => '.tmb',
                'quarantine' => '.quarantine',
                'winHashFix' => DIRECTORY_SEPARATOR !== '/',
            ]],
        ];
    }
}
