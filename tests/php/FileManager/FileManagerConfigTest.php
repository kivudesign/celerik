<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\FileManager;

use Celerik\Ui\Components\FileManager;
use Celerik\Ui\FileManager\FileManagerConfig;
use Celerik\Ui\Upload\UploadRules;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FileManagerConfigTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/celerik-fm-test-' . bin2hex(random_bytes(4));
        mkdir($this->root);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->root);
    }

    public function testBuildsALockedDownLocalVolume(): void
    {
        $root = FileManagerConfig::local($this->root, UploadRules::types('image/png')->maxSize('1M'))
            ->alias('Documents')
            ->toArray()['roots'][0];

        self::assertSame('LocalFileSystem', $root['driver']);
        self::assertSame(realpath($this->root), $root['path']);
        self::assertSame('', $root['URL'], 'Files must only be served through the connector.');
        self::assertSame('Documents', $root['alias']);
        self::assertSame('finfo', $root['mimeDetect']);
        self::assertSame(['image/png'], $root['uploadAllow']);
        self::assertSame(['all'], $root['uploadDeny']);
        self::assertSame(['deny', 'allow'], $root['uploadOrder']);
        self::assertSame(1024 * 1024, $root['uploadMaxSize']);
        self::assertFalse($root['uploadOverwrite']);
        self::assertSame(['read' => true, 'write' => true], $root['defaults']);
        self::assertSame(['archive', 'extract', 'netmount', 'chmod', 'mkfile', 'put', 'zipdl', 'resize'], $root['disabled']);
        self::assertDoesNotMatchRegularExpression('~' . $root['dispInlineRegex'] . '~', 'image/svg+xml');
        self::assertDoesNotMatchRegularExpression('~' . $root['dispInlineRegex'] . '~', 'text/html');
        self::assertMatchesRegularExpression('~' . $root['dispInlineRegex'] . '~', 'image/png');
    }

    public function testReadOnlyDisablesEveryWriteCommand(): void
    {
        $config = FileManagerConfig::local($this->root)->readOnly();

        self::assertSame(['read' => true, 'write' => false], $config->toArray()['roots'][0]['defaults']);
        foreach (FileManagerConfig::WRITE_COMMANDS as $command) {
            self::assertContains($command, $config->disabledCommands());
        }
    }

    public function testOptionalCommandsCanBeEnabledButDangerousOnesNever(): void
    {
        $config = FileManagerConfig::local($this->root)->allow('resize', 'zipdl');

        self::assertNotContains('resize', $config->disabledCommands());
        self::assertContains('mkfile', $config->disabledCommands());

        $this->expectException(InvalidArgumentException::class);
        $config->allow('extract');
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function names(): iterable
    {
        yield 'plain' => ['report.pdf', true];
        yield 'folder' => ['Invoices 2026', true];
        yield 'unicode' => ['café.png', true];
        yield 'dotfile' => ['.htaccess', false];
        yield 'user.ini' => ['.user.ini', false];
        yield 'php' => ['shell.php', false];
        yield 'double extension' => ['photo.php.jpg', false];
        yield 'phtml' => ['x.PHTML', false];
        yield 'svg' => ['logo.svg', false];
        yield 'html' => ['page.html', false];
        yield 'slash' => ['../secret', false];
        yield 'backslash' => ['..\\secret', false];
        yield 'nul byte' => ["a.png\0.php", false];
        yield 'windows reserved char' => ['a:b.png', false];
        yield 'empty' => ['', false];
    }

    #[DataProvider('names')]
    public function testAcceptsOnlySafeNames(string $name, bool $accepted): void
    {
        self::assertSame($accepted, FileManagerConfig::isAcceptedName($name));
    }

    public function testDotfilesAreHiddenAndInaccessible(): void
    {
        self::assertTrue(FileManagerConfig::access('hidden', '/data/.tmb'));
        self::assertTrue(FileManagerConfig::access('locked', '/data/.htaccess'));
        self::assertFalse(FileManagerConfig::access('read', '/data/.htaccess'));
        self::assertFalse(FileManagerConfig::access('write', '/data/.htaccess'));
        self::assertNull(FileManagerConfig::access('read', '/data/report.pdf'));
    }

    public function testUploadHookRejectsFilesWhoseContentDoesNotMatch(): void
    {
        $hook = FileManagerConfig::local($this->root, UploadRules::types('image/png'))->toArray()['bind']['upload.presave'][0];

        $php = $this->root . '/upload1';
        file_put_contents($php, '<?php system($_GET["c"]);');
        $png = $this->root . '/upload2';
        file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));

        $hash = 'l1_Lw';
        $name = 'avatar.png';
        self::assertSame(['error' => 'This file type is not allowed.'], $hook($hash, $name, $php));
        self::assertTrue($hook($hash, $name, $png));

        $name = 'avatar.pdf';
        self::assertSame(['error' => 'The file extension does not match its content.'], $hook($hash, $name, $png));
    }

    public function testRootMustExist(): void
    {
        $this->expectException(InvalidArgumentException::class);

        FileManagerConfig::local($this->root . '/missing');
    }

    public function testComponentRendersALabelledRegionAndRequiresAConnector(): void
    {
        $manager = FileManager::make('files')
            ->url('/files/connector')
            ->assetsUrl('/vendor/elfinder/')
            ->title('Shared files')
            ->lang('fr')
            ->csrf('tok');

        self::assertStringContainsString('role="region" aria-label="Shared files"', $manager->render());
        self::assertSame([
            'url' => '/files/connector',
            'assetsUrl' => '/vendor/elfinder',
            'height' => 500,
            'lang' => 'fr',
            'csrf' => ['header' => 'X-CSRF-TOKEN', 'token' => 'tok'],
        ], $manager->toArray());

        $this->expectException(LogicException::class);
        FileManager::make()->render();
    }
}
