<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * The API reference of the documentation site (site/tools/reference.php) lists every public
 * class and method of src/, so adding a method documents it.
 */
final class SiteReferenceTest extends TestCase
{
    private string $output;

    protected function setUp(): void
    {
        $this->output = sys_get_temp_dir() . '/celerik-reference-' . bin2hex(random_bytes(4));
        $script = dirname(__DIR__, 2) . '/site/tools/reference.php';
        exec(sprintf('%s %s %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($script), escapeshellarg($this->output)), $lines, $code);
        self::assertSame(0, $code, implode("\n", $lines));
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->output . '/*') ?: []);
        rmdir($this->output);
    }

    public function testEveryPublicClassAndMethodIsDocumented(): void
    {
        $pages = '';
        foreach (glob($this->output . '/*.md') ?: [] as $page) {
            $pages .= file_get_contents($page);
        }

        $missing = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2) . '/src', FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $relative = substr($file->getPathname(), strlen(dirname(__DIR__, 2) . '/src/'), -4);
            $class = 'Celerik\\Ui\\' . str_replace(['/', '\\'], '\\', $relative);
            try {
                $reflection = new ReflectionClass($class);
            } catch (Throwable) {
                continue; // an adapter whose framework is not installed (the script stubs it)
            }
            if (!str_contains($pages, "## {$reflection->getShortName()}\n\n`")) {
                $missing[] = $class;
                continue;
            }
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $name = $method->getName();
                $documented = !str_starts_with($name, '__') || $name === '__construct';
                if ($method->getDeclaringClass()->getName() !== $class || !$documented) {
                    continue;
                }
                if (!str_contains($pages, "#### $name\n")) {
                    $missing[] = $class . '::' . $name;
                }
            }
        }

        self::assertSame([], $missing);
    }

    public function testDocblocksAreSafeForTheSite(): void
    {
        $components = (string) file_get_contents($this->output . '/components.md');

        self::assertStringStartsWith("# Celerik\\Ui\\Components\n\n::: v-pre\n", $components, 'Twig examples ({{ }}) are not evaluated.');
        self::assertStringContainsString('`{"data": $chart->toData()}`', $components, 'Inline code is not escaped.');
        self::assertStringContainsString("dataset(string \$label, array \$data, array \$options = []): self", $components);
        self::assertStringNotContainsString('<script', $components);
        self::assertFileExists($this->output . '/reference.json');
        self::assertFileExists($this->output . '/index.md');
    }
}
