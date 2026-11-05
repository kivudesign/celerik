<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Upload;

use Celerik\Ui\Upload\UploadRules;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UploadRulesTest extends TestCase
{
    public function testBuildsAcceptAttributeAndHelpText(): void
    {
        $rules = UploadRules::types('image/png', 'application/pdf')->maxSize('2M')->maxFiles(3);

        self::assertSame('image/png,.png,application/pdf,.pdf', $rules->accept());
        self::assertSame('PNG, PDF. Up to 2 MB per file, 3 files at most.', $rules->describe());
        self::assertSame(2 * 1024 * 1024, $rules->maxSize);
        self::assertTrue($rules->allows('application/pdf', 'PDF'));
        self::assertFalse($rules->allows('application/pdf', 'png'));
    }

    public function testParsesSizes(): void
    {
        self::assertSame(500 * 1024, UploadRules::images()->maxSize('500K')->maxSize);
        self::assertSame(1024 ** 3, UploadRules::images()->maxSize('1 GB')->maxSize);
        self::assertSame(1234, UploadRules::images()->maxSize(1234)->maxSize);
        self::assertSame('1.5 MB', UploadRules::formatSize(1536 * 1024));
    }

    public function testCustomTypesExtendKnownOnes(): void
    {
        $rules = UploadRules::images()->withType('application/x-custom', ['cst']);

        self::assertTrue($rules->allows('application/x-custom', 'cst'));
        self::assertTrue($rules->allows('image/jpeg', 'jpeg'));
    }

    /**
     * @return iterable<string, array{callable(): mixed}>
     */
    public static function forbidden(): iterable
    {
        yield 'svg type' => [static fn () => UploadRules::images()->withType('image/svg+xml', ['svg'])];
        yield 'html type' => [static fn () => UploadRules::images()->withType('text/html', ['txt'])];
        yield 'php extension' => [static fn () => UploadRules::images()->withType('text/plain', ['php'])];
        yield 'phtml extension' => [static fn () => UploadRules::images()->withType('application/x-foo', ['phtml'])];
        yield 'invalid extension' => [static fn () => UploadRules::images()->withType('application/x-foo', ['a.b'])];
        yield 'no extension' => [static fn () => UploadRules::images()->withType('application/x-foo', [])];
        yield 'unknown type' => [static fn () => UploadRules::types('application/x-unknown')];
        yield 'bad size' => [static fn () => UploadRules::images()->maxSize('lots')];
        yield 'too many files' => [static fn () => UploadRules::images()->maxFiles(1000)];
    }

    #[DataProvider('forbidden')]
    public function testRejectsDangerousOrInvalidRules(callable $build): void
    {
        $this->expectException(InvalidArgumentException::class);

        $build();
    }
}
