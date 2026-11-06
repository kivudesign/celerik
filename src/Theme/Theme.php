<?php

declare(strict_types=1);

namespace Celerik\Ui\Theme;

use InvalidArgumentException;
use JsonException;

/**
 * A Celerik theme: design tokens on top of a base look (theme/theme.schema.json).
 *
 *     $theme = Theme::preset('tailwind')->name('Acme')->color('light', 'primary', '#0b5394');
 *     $theme->contrast();   // WCAG report, see Contrast
 *
 * Every value is validated against a strict format (hex colors, plain font names, numbers),
 * so a theme loaded from user input can never inject CSS into the generated stylesheet.
 */
final class Theme
{
    public const BASES = ['bootstrap', 'material', 'tailwind'];

    public const MODES = ['light', 'dark'];

    public const TOKENS = [
        'primary', 'primaryHover', 'onPrimary', 'bodyBg', 'bodyColor',
        'muted', 'surface', 'border', 'inputBorder', 'danger',
    ];

    public const SHADOWS = ['none', 'subtle', 'elevated', 'outlined'];

    private const KEYS = ['name', 'base', 'light', 'dark', 'radius', 'fontFamily', 'shadow'];

    private string $name;

    private string $base;

    /** @var array<string, array<string, string>> mode => token => #rrggbb */
    private array $palettes = [];

    private ?float $radius = null;

    private ?string $fontFamily = null;

    private ?string $shadow = null;

    /**
     * @param array<string, string> $light
     * @param array<string, string> $dark
     */
    private function __construct(string $name, string $base, array $light, array $dark)
    {
        $this->name($name)->base($base);
        foreach (['light' => $light, 'dark' => $dark] as $mode => $palette) {
            $missing = array_diff(self::TOKENS, array_keys($palette));
            $unknown = array_diff(array_keys($palette), self::TOKENS);
            if ($missing !== [] || $unknown !== []) {
                throw new InvalidArgumentException(sprintf(
                    'The %s palette needs exactly these colors: %s.%s',
                    $mode,
                    implode(', ', self::TOKENS),
                    $unknown === [] ? '' : ' Unknown: ' . implode(', ', $unknown) . '.',
                ));
            }
            foreach ($palette as $token => $color) {
                $this->color($mode, $token, $color);
            }
        }
    }

    /** One of the shipped themes, from theme/presets/{name}.json. */
    public static function preset(string $name): self
    {
        if (!in_array($name, self::BASES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown preset "%s". Available: %s.',
                $name,
                implode(', ', self::BASES),
            ));
        }

        return self::fromJson((string) file_get_contents(self::presetPath($name)));
    }

    public static function presetPath(string $name): string
    {
        return dirname(__DIR__, 2) . '/theme/presets/' . $name . '.json';
    }

    /**
     * @param array<mixed> $data A theme as in theme/theme.schema.json.
     * @throws InvalidArgumentException When a value is missing, unknown or malformed.
     */
    public static function fromArray(array $data): self
    {
        $unknown = array_diff(array_keys($data), self::KEYS);
        if ($unknown !== []) {
            throw new InvalidArgumentException('Unknown theme keys: ' . implode(', ', $unknown) . '.');
        }
        foreach (['name', 'base', 'light', 'dark'] as $key) {
            if (!isset($data[$key])) {
                throw new InvalidArgumentException(sprintf('The theme has no "%s".', $key));
            }
        }
        if (!is_string($data['name']) || !is_string($data['base'])) {
            throw new InvalidArgumentException('The theme name and base must be strings.');
        }
        if (!is_array($data['light']) || !is_array($data['dark'])) {
            throw new InvalidArgumentException('The light and dark palettes must be objects.');
        }

        $theme = new self($data['name'], $data['base'], self::strings($data['light']), self::strings($data['dark']));

        if (isset($data['radius'])) {
            if (!is_int($data['radius']) && !is_float($data['radius'])) {
                throw new InvalidArgumentException('The radius must be a number (rem).');
            }
            $theme->radius((float) $data['radius']);
        }
        if (isset($data['fontFamily'])) {
            $theme->fontFamily(is_string($data['fontFamily']) ? $data['fontFamily'] : '');
        }
        if (isset($data['shadow'])) {
            $theme->shadow(is_string($data['shadow']) ? $data['shadow'] : '');
        }

        return $theme;
    }

    /** @throws InvalidArgumentException When the JSON is invalid or the theme is malformed. */
    public static function fromJson(string $json): self
    {
        try {
            $data = json_decode($json, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Invalid theme JSON: ' . $exception->getMessage(), 0, $exception);
        }
        if (!is_array($data)) {
            throw new InvalidArgumentException('A theme must be a JSON object.');
        }

        return self::fromArray($data);
    }

    public function name(string $name): self
    {
        if (preg_match('/^[\p{L}\p{N} ._-]{1,60}$/u', $name) !== 1) {
            throw new InvalidArgumentException(
                'A theme name has 1 to 60 letters, digits, spaces, dots, dashes or underscores.',
            );
        }
        $this->name = $name;

        return $this;
    }

    public function base(string $base): self
    {
        if (!in_array($base, self::BASES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown base "%s". Available: %s.',
                $base,
                implode(', ', self::BASES),
            ));
        }
        $this->base = $base;

        return $this;
    }

    /** @param string $color "#rrggbb" */
    public function color(string $mode, string $token, string $color): self
    {
        if (!in_array($mode, self::MODES, true)) {
            throw new InvalidArgumentException(sprintf('Unknown mode "%s": use light or dark.', $mode));
        }
        if (!in_array($token, self::TOKENS, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown color "%s". Available: %s.',
                $token,
                implode(', ', self::TOKENS),
            ));
        }
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color) !== 1) {
            throw new InvalidArgumentException(
                sprintf('Invalid color "%s" for %s.%s: use #rrggbb.', $color, $mode, $token),
            );
        }
        $this->palettes[$mode][$token] = strtolower($color);

        return $this;
    }

    /** Corner radius in rem (0 to 2), or null to keep the base theme's. */
    public function radius(?float $rem): self
    {
        if ($rem !== null && ($rem < 0 || $rem > 2)) {
            throw new InvalidArgumentException('The radius must be between 0 and 2 rem.');
        }
        $this->radius = $rem === null ? null : round($rem, 3);

        return $this;
    }

    /** A font stack such as "Inter, system-ui, sans-serif", or null to keep the base theme's. */
    public function fontFamily(?string $stack): self
    {
        if ($stack !== null && preg_match('/^[A-Za-z0-9 ,\'"-]{1,200}$/', $stack) !== 1) {
            throw new InvalidArgumentException(
                'A font stack has only family names, spaces, commas, quotes and dashes.',
            );
        }
        $this->fontFamily = $stack;

        return $this;
    }

    /** none, subtle, elevated or outlined, or null to keep the base theme's. */
    public function shadow(?string $shadow): self
    {
        if ($shadow !== null && !in_array($shadow, self::SHADOWS, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown shadow "%s". Available: %s.',
                $shadow,
                implode(', ', self::SHADOWS),
            ));
        }
        $this->shadow = $shadow;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBase(): string
    {
        return $this->base;
    }

    /** @return array<string, string> token => #rrggbb */
    public function palette(string $mode): array
    {
        if (!in_array($mode, self::MODES, true)) {
            throw new InvalidArgumentException(sprintf('Unknown mode "%s": use light or dark.', $mode));
        }

        return $this->palettes[$mode];
    }

    /** A color as "r, g, b", for Bootstrap's *-rgb custom properties. */
    public function rgb(string $mode, string $token): string
    {
        $color = $this->palette($mode)[$token]
            ?? throw new InvalidArgumentException(sprintf('Unknown color "%s".', $token));

        return implode(', ', array_map('hexdec', str_split(substr($color, 1), 2)));
    }

    public function getRadius(): ?float
    {
        return $this->radius;
    }

    public function getFontFamily(): ?string
    {
        return $this->fontFamily;
    }

    public function getShadow(): ?string
    {
        return $this->shadow;
    }

    /**
     * The theme's stylesheet, to load after celerik.css and the base theme's stylesheet
     * (Assets::tags(theme: $theme->getBase(), customTheme: '/css/acme.css')).
     */
    public function toCss(): string
    {
        return Stylesheet::render($this);
    }

    /** @return list<ContrastResult> */
    public function contrast(): array
    {
        return Contrast::report($this);
    }

    /** @return array<string, mixed> The theme as in theme/theme.schema.json. */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'base' => $this->base,
            'light' => $this->palettes['light'],
            'dark' => $this->palettes['dark'],
            'radius' => $this->radius,
            'fontFamily' => $this->fontFamily,
            'shadow' => $this->shadow,
        ], static fn (mixed $value): bool => $value !== null);
    }

    public function toJson(): string
    {
        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

        return json_encode($this->toArray(), $flags) . "\n";
    }

    /**
     * @param array<mixed> $values
     * @return array<string, string>
     */
    private static function strings(array $values): array
    {
        $strings = [];
        foreach ($values as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                throw new InvalidArgumentException('Palette colors must be strings, e.g. "primary": "#0d6efd".');
            }
            $strings[$key] = $value;
        }

        return $strings;
    }
}
