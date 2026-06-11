<?php

declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Psr7\Utils;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class BucketService
{
    public function __construct(
        private Filesystem $filesystem,
        private ?string $attachmentUrlPrefix,
        private ?string $bucketPrefix,
        private array $allowedMimeTypes = [
            'image/png',
            'image/jpeg',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'application/pdf',
            'text/plain',
            'text/csv',
            'application/json',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.oasis.opendocument.spreadsheet',
        ],
        private array $allowedExtensions = [
            'png',
            'jpg',
            'jpeg',
            'gif',
            'webp',
            'svg',
            'pdf',
            'txt',
            'csv',
            'json',
            'xlsx',
            'ods',
        ],
        private int $maxFileSize = 25 * 1024 * 1024,
    ) {
    }

    public function uploadFile(UploadedFile $file, string $dir): string
    {
        if ($file->getSize() > $this->maxFileSize) {
            throw new \InvalidArgumentException(\sprintf('File size %d bytes exceeds the maximum allowed size of %d bytes.', $file->getSize(), $this->maxFileSize));
        }

        $ext = $file->guessExtension() ?? 'bin';
        if (!\in_array(strtolower($ext), $this->allowedExtensions, true)) {
            throw new \InvalidArgumentException(\sprintf('Disallowed file extension: %s', $ext));
        }

        $mime = $file->getMimeType();
        if (null !== $mime && !\in_array($mime, $this->allowedMimeTypes, true)) {
            throw new \InvalidArgumentException(\sprintf('Disallowed MIME type: %s', $mime));
        }

        $name = bin2hex(random_bytes(16));
        $path = \sprintf('/%s/%s.%s', trim($dir, '/'), $name, $ext);

        $stream = Utils::tryFopen($file->getRealPath(), 'rb');
        $this->filesystem->writeStream($path, $stream, [
            'visibility' => 'public',
            'directory_visibility' => 'public',
        ]);

        $url = '';
        if (null !== $this->attachmentUrlPrefix && '' !== $this->attachmentUrlPrefix) {
            $url = $this->attachmentUrlPrefix;
        }

        if (null !== $this->bucketPrefix && '' !== $this->bucketPrefix) {
            $url .= '/' . $this->bucketPrefix;
        }

        return $url . $path;
    }
}
