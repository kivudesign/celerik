<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Upload;

use Celerik\Ui\Upload\UploadedFile;
use Celerik\Ui\Upload\UploadException;
use Celerik\Ui\Upload\UploadHandler;
use Celerik\Ui\Upload\UploadRules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UploadHandlerTest extends TestCase
{
    /** A valid 1×1 PNG. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private string $root;
    private UploadHandler $handler;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/celerik-upload-test-' . bin2hex(random_bytes(4));
        mkdir($this->root . '/incoming', 0700, true);
        $this->handler = new UploadHandler(
            UploadRules::types('image/png', 'image/gif', 'application/pdf')->maxSize('1K'),
            $this->root . '/tmp',
        );
    }

    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->root);
    }

    public function testProcessThenClaimStoresTheFileUnderARandomName(): void
    {
        $id = $this->handler->process($this->upload('../../avatar.png', base64_decode(self::PNG)));

        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $id);
        self::assertFileExists($this->root . '/tmp/' . $id);

        $stored = $this->handler->claim($id, $this->root . '/avatars');

        self::assertMatchesRegularExpression('/^[a-f0-9]{32}\.png$/', $stored->name);
        self::assertSame($this->root . '/avatars/' . $stored->name, $stored->path);
        self::assertSame('avatar.png', $stored->originalName, 'Path segments must be stripped from the client name.');
        self::assertSame('image/png', $stored->mimeType);
        self::assertFileExists($stored->path);
        self::assertFileDoesNotExist($this->root . '/tmp/' . $id);
        self::assertSame(['name', 'originalName', 'mimeType', 'size'], array_keys($stored->jsonSerialize()));
    }

    public function testAnIdCanOnlyBeClaimedOnce(): void
    {
        $id = $this->handler->process($this->upload('a.png', base64_decode(self::PNG)));
        $this->handler->claim($id, $this->root . '/final');

        $this->expectException(UploadException::class);
        $this->handler->claim($id, $this->root . '/final');
    }

    public function testRevertDeletesTheTemporaryFile(): void
    {
        $id = $this->handler->process($this->upload('a.png', base64_decode(self::PNG)));

        self::assertTrue($this->handler->revert($id));
        self::assertFileDoesNotExist($this->root . '/tmp/' . $id);
        self::assertFalse($this->handler->revert($id));
        self::assertFalse($this->handler->revert('../../etc/passwd'));
    }

    public function testClaimRejectsInvalidIds(): void
    {
        file_put_contents($this->root . '/secret.txt', 'secret');

        $this->expectException(UploadException::class);
        $this->handler->claim('../secret.txt', $this->root . '/final');
    }

    public function testStoreValidatesAndMovesDirectly(): void
    {
        $stored = $this->handler->store($this->upload('report.PDF', "%PDF-1.4\n%âãÏÓ\n1 0 obj\n<<>>\nendobj\n"), $this->root . '/docs');

        self::assertSame('application/pdf', $stored->mimeType);
        self::assertStringEndsWith('.pdf', $stored->name);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function rejectedUploads(): iterable
    {
        $png = base64_decode(self::PNG);

        yield 'php code renamed to png' => ['avatar.png', "<?php system(\$_GET['c']);", 'This file type is not allowed.'];
        yield 'double extension' => ['shell.php.gif', "GIF89a<?php system(\$_GET['c']);", 'This file type is not allowed.'];
        yield 'phtml anywhere' => ['photo.phtml.png', $png, 'This file type is not allowed.'];
        yield 'png content with pdf name' => ['report.pdf', $png, 'The file extension does not match its content.'];
        yield 'no extension' => ['avatar', $png, 'The file extension does not match its content.'];
        yield 'svg with script' => ['logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'This file type is not allowed.'];
        yield 'html' => ['page.png', '<!doctype html><html><script>alert(1)</script></html>', 'This file type is not allowed.'];
        yield 'empty' => ['empty.png', '', 'The file is empty.'];
        yield 'too large' => ['big.png', $png . str_repeat("\0", 2048), 'The file is too large (1 KB at most).'];
    }

    #[DataProvider('rejectedUploads')]
    public function testRejectsDangerousOrInvalidFiles(string $name, string $content, string $message): void
    {
        $this->expectException(UploadException::class);
        $this->expectExceptionMessage($message);

        $this->handler->process($this->upload($name, $content));
    }

    public function testUploadErrorsHaveUserFacingMessages(): void
    {
        $this->expectExceptionMessage('The file is too large.');

        $this->handler->process(new UploadedFile('', 'a.png', UPLOAD_ERR_INI_SIZE));
    }

    public function testFromGlobalsRejectsPathsThatWereNotUploaded(): void
    {
        $files = UploadedFile::fromGlobals([
            'name' => ['a.png', 'b.png', ''],
            'tmp_name' => [__FILE__, __FILE__, ''],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_PARTIAL, UPLOAD_ERR_NO_FILE],
        ]);

        self::assertCount(2, $files, 'Empty slots of a multiple input are skipped.');
        self::assertSame(UPLOAD_ERR_CANT_WRITE, $files[0]->error, 'A forged $_FILES path must not be trusted.');
        self::assertSame(UPLOAD_ERR_PARTIAL, $files[1]->error);
    }

    public function testPurgeDeletesStaleTemporaryUploads(): void
    {
        $old = $this->handler->process($this->upload('old.png', base64_decode(self::PNG)));
        $fresh = $this->handler->process($this->upload('fresh.png', base64_decode(self::PNG)));
        touch($this->root . '/tmp/' . $old . '.json', time() - 7200);

        self::assertSame(1, $this->handler->purge(3600));
        self::assertFileDoesNotExist($this->root . '/tmp/' . $old);
        self::assertFileExists($this->root . '/tmp/' . $fresh);
    }

    private function upload(string $clientName, string $content): UploadedFile
    {
        $path = $this->root . '/incoming/' . bin2hex(random_bytes(6));
        file_put_contents($path, $content);

        return new UploadedFile($path, $clientName);
    }
}
