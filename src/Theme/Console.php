<?php

declare(strict_types=1);

namespace Celerik\Ui\Theme;

use InvalidArgumentException;

/**
 * The celerik-theme command: build a theme stylesheet from theme.json, check its contrast,
 * or start a theme from a preset.
 *
 *     vendor/bin/celerik-theme preset tailwind > theme.json
 *     vendor/bin/celerik-theme check theme.json
 *     vendor/bin/celerik-theme build theme.json public/css/acme.css
 *
 * Exit codes: 0 done, 1 a contrast check failed, 2 invalid usage or an invalid theme.
 */
final class Console
{
    public const OK = 0;

    public const CONTRAST_FAILED = 1;

    public const INVALID = 2;

    private const USAGE = <<<'TXT'
        Usage:
          celerik-theme preset <bootstrap|material|tailwind>   Print a preset as theme JSON
          celerik-theme check <theme.json>                      Print the WCAG contrast report
          celerik-theme build <theme.json> <output.css>         Write the theme stylesheet

        Options:
          --allow-contrast-failures   build: write the stylesheet even if a pair fails
          --quiet                     check: print failures only

        Exit codes: 0 done, 1 a contrast check failed, 2 invalid usage or theme.

        TXT;

    /** @var resource */
    private $out;

    /** @var resource */
    private $err;

    /**
     * @param resource $out
     * @param resource $err
     */
    public function __construct($out, $err)
    {
        $this->out = $out;
        $this->err = $err;
    }

    /** @param list<string> $argv Arguments without the script name. */
    public function run(array $argv): int
    {
        $options = array_values(array_filter($argv, static fn (string $arg): bool => str_starts_with($arg, '--')));
        $arguments = array_values(array_filter($argv, static fn (string $arg): bool => !str_starts_with($arg, '--')));
        $command = $arguments[0] ?? 'help';

        $known = ['--allow-contrast-failures', '--quiet', '--help'];
        $unknown = array_diff($options, $known);
        if ($unknown !== []) {
            return $this->usage('Unknown option: ' . implode(', ', $unknown));
        }
        if ($command === 'help' || in_array('--help', $options, true)) {
            fwrite($this->out, self::USAGE);

            return self::OK;
        }

        try {
            return match ($command) {
                'preset' => $this->preset($arguments),
                'check' => $this->check($arguments, in_array('--quiet', $options, true)),
                'build' => $this->build($arguments, in_array('--allow-contrast-failures', $options, true)),
                default => $this->usage(sprintf('Unknown command "%s".', $command)),
            };
        } catch (InvalidArgumentException $exception) {
            fwrite($this->err, 'Invalid theme: ' . $exception->getMessage() . "\n");

            return self::INVALID;
        }
    }

    /** @param list<string> $arguments */
    private function preset(array $arguments): int
    {
        if (count($arguments) !== 2) {
            return $this->usage('preset needs a name.');
        }
        fwrite($this->out, Theme::preset($arguments[1])->toJson());

        return self::OK;
    }

    /** @param list<string> $arguments */
    private function check(array $arguments, bool $quiet): int
    {
        if (count($arguments) !== 2) {
            return $this->usage('check needs a theme file.');
        }
        $theme = $this->load($arguments[1]);
        if ($theme === null) {
            return self::INVALID;
        }

        $failures = 0;
        foreach (Contrast::report($theme) as $result) {
            $failures += $result->passes() ? 0 : 1;
            if (!$quiet || !$result->passes()) {
                fwrite($this->out, $result . "\n");
            }
        }
        fwrite($this->out, $failures === 0
            ? sprintf("%s: every pair meets its minimum.\n", $theme->getName())
            : sprintf("%s: %d pair%s below the minimum.\n", $theme->getName(), $failures, $failures === 1 ? '' : 's'));

        return $failures === 0 ? self::OK : self::CONTRAST_FAILED;
    }

    /** @param list<string> $arguments */
    private function build(array $arguments, bool $allowFailures): int
    {
        if (count($arguments) !== 3) {
            return $this->usage('build needs a theme file and an output file.');
        }
        $theme = $this->load($arguments[1]);
        if ($theme === null) {
            return self::INVALID;
        }

        $failures = Contrast::failures($theme);
        foreach ($failures as $failure) {
            fwrite($this->err, $failure . "\n");
        }
        if ($failures !== [] && !$allowFailures) {
            fwrite($this->err, "Not written: fix the colors, or pass --allow-contrast-failures.\n");

            return self::CONTRAST_FAILED;
        }

        $output = $arguments[2];
        $directory = dirname($output);
        if (!is_dir($directory) || file_put_contents($output, $theme->toCss()) === false) {
            fwrite($this->err, sprintf("Cannot write %s.\n", $output));

            return self::INVALID;
        }
        fwrite($this->out, sprintf("Wrote %s (base: %s).\n", $output, $theme->getBase()));

        return $failures === [] ? self::OK : self::CONTRAST_FAILED;
    }

    private function load(string $file): ?Theme
    {
        if (!is_file($file) || !is_readable($file)) {
            fwrite($this->err, sprintf("Cannot read %s.\n", $file));

            return null;
        }

        return Theme::fromJson((string) file_get_contents($file));
    }

    private function usage(string $message): int
    {
        fwrite($this->err, $message . "\n\n" . self::USAGE);

        return self::INVALID;
    }
}
