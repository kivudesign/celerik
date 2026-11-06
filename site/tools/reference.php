<?php

/**
 * Generates the API reference of the documentation site from the PHP code (npm run
 * docs:reference, run before docs:dev and docs:build):
 *
 *   site/reference/index.md          the list of namespaces and classes
 *   site/reference/<namespace>.md    every public class, constant and method, with its docblock
 *   site/.generated/reference.json   the sidebar
 *
 * Everything public in src/ appears, so the reference never falls out of date. Write
 * docblocks for public methods: their first paragraph is the description.
 *
 *     php site/tools/reference.php [output directory]
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

// The framework adapters extend classes of optional packages (Laravel's view component,
// Symfony's bundle). When one is not installed, an empty stand-in lets reflection read
// the adapter: only its own methods are documented anyway.
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'Celerik\\') || !preg_match('/^[A-Za-z_][A-Za-z0-9_\\\\]*$/', $class)) {
        return;
    }
    $parts = explode('\\', $class);
    $short = array_pop($parts);
    $namespace = implode('\\', $parts);
    $kind = str_ends_with($short, 'Interface') ? 'interface' : 'abstract class';
    eval(($namespace === '' ? '' : "namespace $namespace; ") . "$kind $short {}");
});

$output = $argv[1] ?? dirname(__DIR__) . '/reference';
$sidebarFile = isset($argv[1]) ? $output . '/reference.json' : dirname(__DIR__) . '/.generated/reference.json';

// HTML characters in prose are escaped, except inside `inline code`, which shows them as is.
$escapeProse = static function (string $line): string {
    $parts = explode('`', $line);
    foreach ($parts as $i => $part) {
        if ($i % 2 === 0) {
            $parts[$i] = htmlspecialchars($part, ENT_NOQUOTES);
        }
    }
    return implode('`', $parts);
};

// Prose from a docblock, safe in Markdown rendered by Vue: HTML characters are escaped and
// lines indented by four spaces or more (code examples) become PHP code blocks.
$docblock = static function (string|false $comment) use ($escapeProse): array {
    if ($comment === false) {
        return ['text' => '', 'params' => [], 'throws' => []];
    }
    $lines = preg_split('/\R/', (string) preg_replace('#^/\*\*|\*/$#', '', $comment)) ?: [];
    $lines = array_map(
        static fn (string $line): string => (string) preg_replace('/^\s*\* ?/', '', rtrim($line)),
        $lines,
    );

    $text = [];
    $params = [];
    $throws = [];
    $code = [];
    $flushCode = static function () use (&$code, &$text): void {
        if ($code !== []) {
            $text[] = "```php\n" . implode("\n", $code) . "\n```";
            $code = [];
        }
    };
    foreach ($lines as $line) {
        if (preg_match('/^@param\s+\S+\s+(?:\.\.\.)?\$(\w+)\s*(.*)$/', $line, $match) === 1) {
            $flushCode();
            if (trim($match[2]) !== '') {
                $params[$match[1]] = trim($match[2]);
            }
            continue;
        }
        if (preg_match('/^@throws\s+(\S+)\s*(.*)$/', $line, $match) === 1) {
            $throws[] = trim($match[1] . ' ' . $match[2]);
            continue;
        }
        if (str_starts_with($line, '@')) {
            continue; // @return, @var, @phpstan…: the types are in the signature
        }
        if (preg_match('/^ {4}(.*)$/', $line, $match) === 1) {
            $code[] = $match[1];
            continue;
        }
        $flushCode();
        $text[] = $escapeProse($line);
    }
    $flushCode();

    $prose = trim((string) preg_replace("/\n{3,}/", "\n\n", implode("\n", $text)));

    return [
        'text' => $prose,
        'params' => array_map($escapeProse, $params),
        'throws' => array_map($escapeProse, $throws),
    ];
};

$type = static function (?ReflectionType $type): string {
    if ($type === null) {
        return '';
    }
    return (string) preg_replace('/\b(?:[A-Za-z0-9_]+\\\\)+/', '', (string) $type);
};

// A value as short PHP: ['a', 'b'], ['key' => 1], null, true, 'text'.
$export = static function (mixed $value) use (&$export): string {
    if (is_array($value)) {
        $items = [];
        foreach ($value as $key => $item) {
            $items[] = (array_is_list($value) ? '' : var_export($key, true) . ' => ') . $export($item);
        }
        return '[' . implode(', ', $items) . ']';
    }
    return $value === null ? 'null' : var_export($value, true);
};
$value = static function (mixed $value) use ($export): string {
    $text = $export($value);
    return strlen($text) > 160 ? substr($text, 0, 157) . '…' : $text;
};

$signature = static function (ReflectionMethod $method) use ($type, $value): string {
    $params = array_map(static function (ReflectionParameter $p) use ($type, $value): string {
        $text = trim($type($p->getType()) . ' ' . ($p->isVariadic() ? '...' : '') . '$' . $p->getName());
        if ($p->isDefaultValueAvailable()) {
            $default = $p->isDefaultValueConstant()
                ? (string) $p->getDefaultValueConstantName()
                : $value($p->getDefaultValue());
            $text .= ' = ' . $default;
        }
        return $text;
    }, $method->getParameters());
    $return = $type($method->getReturnType());
    $static = $method->isStatic() ? 'static ' : '';

    return $static . $method->getName() . '(' . implode(', ', $params) . ')' . ($return === '' ? '' : ': ' . $return);
};

// Every class, interface and trait in src/, by namespace.
$byNamespace = [];
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src', FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    $relative = substr($file->getPathname(), strlen($root . '/src/'), -4);
    $class = 'Celerik\\Ui\\' . str_replace(['/', '\\'], '\\', $relative);
    try {
        $reflection = new ReflectionClass($class);
    } catch (Throwable $exception) {
        fwrite(STDERR, "Skipped $class: " . $exception->getMessage() . "\n");
        continue;
    }
    $byNamespace[$reflection->getNamespaceName()][$reflection->getShortName()] = $reflection;
}
ksort($byNamespace);

if (!is_dir($output)) {
    mkdir($output, 0755, true);
}
foreach (glob($output . '/*.md') ?: [] as $old) {
    unlink($old);
}

// Celerik\Ui\Adapters\Laravel => adapters-laravel; Celerik\Ui => core.
$slug = static function (string $namespace): string {
    $relative = trim(str_replace('\\', '-', substr($namespace, strlen('Celerik\\Ui'))), '-');
    return strtolower($relative) ?: 'core';
};
$title = static fn (string $namespace): string => $namespace;
$ownMethod = static fn (ReflectionMethod $m, ReflectionClass $class): bool
    => $m->getDeclaringClass()->getName() === $class->getName();

$index = "# API reference\n\n"
    . "Every public class of `celerik/ui`, generated from the PHP code and its docblocks.\n\n::: v-pre\n\n";
$sidebar = [];
$count = 0;

foreach ($byNamespace as $namespace => $classes) {
    ksort($classes);
    $page = '# ' . $title($namespace) . "\n\n::: v-pre\n\n";
    $index .= '## [' . $title($namespace) . '](./' . $slug($namespace) . ")\n\n";
    $items = [];

    foreach ($classes as $short => $reflection) {
        $kind = match (true) {
            $reflection->isInterface() => 'interface',
            $reflection->isTrait() => 'trait',
            $reflection->isAbstract() => 'abstract class',
            $reflection->isFinal() => 'final class',
            default => 'class',
        };
        $doc = $docblock($reflection->getDocComment());
        $summary = $doc['text'] === '' ? '' : strtok(str_replace("\n", ' ', explode("\n\n", $doc['text'])[0]), "\n");

        $page .= "## $short\n\n`$kind " . $reflection->getName() . '`';
        if ($reflection->getParentClass() !== false) {
            $page .= ' extends `' . $reflection->getParentClass()->getShortName() . '`';
        }
        $shown = static fn (ReflectionClass $i): bool => str_starts_with($i->getName(), 'Celerik\\')
            || in_array($i->getName(), ['JsonSerializable', 'Stringable'], true);
        $interfaces = array_map(
            static fn (ReflectionClass $i): string => '`' . $i->getShortName() . '`',
            array_filter($reflection->getInterfaces(), $shown),
        );
        if ($interfaces !== []) {
            $page .= ' implements ' . implode(', ', $interfaces);
        }
        $page .= "\n\n" . ($doc['text'] === '' ? '' : $doc['text'] . "\n\n");

        $constants = array_filter(
            $reflection->getReflectionConstants(),
            static fn (ReflectionClassConstant $c): bool => $c->isPublic()
                && $c->getDeclaringClass()->getName() === $reflection->getName(),
        );
        if ($constants !== []) {
            $page .= "### Constants\n\n| Constant | Value |\n|---|---|\n";
            foreach ($constants as $constant) {
                $shownValue = str_replace('|', '\\|', $value($constant->getValue()));
                $page .= '| `' . $constant->getName() . '` | `' . $shownValue . "` |\n";
            }
            $page .= "\n";
        }

        // Own methods, with the constructor but without other magic methods.
        $methods = array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            static fn (ReflectionMethod $m): bool => $ownMethod($m, $reflection)
                && (!str_starts_with($m->getName(), '__') || $m->getName() === '__construct'),
        );
        if ($methods !== []) {
            $page .= "### Methods\n\n";
            foreach ($methods as $method) {
                $methodDoc = $docblock($method->getDocComment());
                $page .= '#### ' . $method->getName() . "\n\n```php\n" . $signature($method) . "\n```\n\n";
                if ($methodDoc['text'] !== '') {
                    $page .= $methodDoc['text'] . "\n\n";
                }
                foreach ($methodDoc['params'] as $name => $description) {
                    $page .= "- `\$$name`: $description\n";
                }
                foreach ($methodDoc['throws'] as $throws) {
                    $page .= "- Throws $throws\n";
                }
                if ($methodDoc['params'] !== [] || $methodDoc['throws'] !== []) {
                    $page .= "\n";
                }
            }
        }

        $inherited = array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            static fn (ReflectionMethod $m): bool => !$ownMethod($m, $reflection)
                && str_starts_with($m->getDeclaringClass()->getName(), 'Celerik\\')
                && !str_starts_with($m->getName(), '__'),
        );
        if ($inherited !== []) {
            $names = array_map(static fn (ReflectionMethod $m): string => '`' . $m->getName() . '()`', $inherited);
            $parent = $reflection->getParentClass() === false ? '' : $reflection->getParentClass()->getShortName();
            $page .= 'Inherited from `' . $parent . '`: ' . implode(', ', $names) . ".\n\n";
        }

        $anchor = strtolower($short);
        $index .= "- [`$short`](./" . $slug($namespace) . "#$anchor)" . ($summary === '' ? '' : ': ' . $summary) . "\n";
        $items[] = ['text' => $short, 'link' => '/reference/' . $slug($namespace) . '#' . $anchor];
        $count++;
    }

    $page .= ":::\n";
    file_put_contents($output . '/' . $slug($namespace) . '.md', $page);
    $index .= "\n";
    $sidebar[] = [
        'text' => $title($namespace),
        'link' => '/reference/' . $slug($namespace),
        'collapsed' => true,
        'items' => $items,
    ];
}

file_put_contents($output . '/index.md', $index . ":::\n");
if (!is_dir(dirname($sidebarFile))) {
    mkdir(dirname($sidebarFile), 0755, true);
}
file_put_contents($sidebarFile, json_encode($sidebar, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

echo "Wrote the API reference: $count classes in " . count($byNamespace) . " namespaces\n";
