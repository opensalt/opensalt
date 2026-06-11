<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Service;

use App\Service\BucketService;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BucketServiceTest extends TestCase
{
    private Filesystem&MockObject $filesystem;
    private BucketService $service;

    private string $tmpFile;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->service = new BucketService(
            $this->filesystem,
            'https://cdn.example.com',
            'uploads',
        );
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'bucket_test_');
        file_put_contents($this->tmpFile, 'test');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    public function testUploadValidImage(): void
    {
        $file = $this->createUploadedFile('photo.jpg', 'image/jpeg', 'jpg', 1024);

        $this->filesystem->expects($this->once())
            ->method('writeStream')
            ->with(
                $this->matchesRegularExpression('#^/items/[0-9a-f]{32}\.jpg$#'),
                $this->anything(),
                $this->anything(),
            );

        $url = $this->service->uploadFile($file, 'items');
        $this->assertStringStartsWith('https://cdn.example.com/uploads/', $url);
        $this->assertStringEndsWith('.jpg', $url);
    }

    public function testUploadRejectsDisallowedExtension(): void
    {
        $file = $this->createUploadedFile('evil.php', 'application/x-php', 'php', 100);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Disallowed file extension');

        $this->service->uploadFile($file, 'items');
    }

    public function testUploadRejectsDisallowedMimeType(): void
    {
        $file = $this->createUploadedFile('data.bin', 'application/x-msdownload', 'bin', 100);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Disallowed file extension');

        $this->service->uploadFile($file, 'items');
    }

    public function testUploadRejectsOversizedFile(): void
    {
        $file = $this->createUploadedFile('big.jpg', 'image/jpeg', 'jpg', 50 * 1024 * 1024);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('exceeds the maximum allowed size');

        $this->service->uploadFile($file, 'items');
    }

    public function testUploadGeneratesRandomFilename(): void
    {
        $file1 = $this->createUploadedFile('a.jpg', 'image/jpeg', 'jpg', 100);
        $file2 = $this->createUploadedFile('b.jpg', 'image/jpeg', 'jpg', 100);

        $this->filesystem->method('writeStream');

        $url1 = $this->service->uploadFile($file1, 'items');
        $url2 = $this->service->uploadFile($file2, 'items');

        $this->assertNotSame($url1, $url2);
    }

    private function createUploadedFile(
        string $originalName,
        string $mimeType,
        string $guessedExt,
        int $size,
    ): UploadedFile&MockObject {
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn($originalName);
        $file->method('getMimeType')->willReturn($mimeType);
        $file->method('guessExtension')->willReturn($guessedExt);
        $file->method('getSize')->willReturn($size);
        $file->method('getRealPath')->willReturn($this->tmpFile);

        return $file;
    }
}
