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
    ) {
    }

    public function uploadFile(UploadedFile $file, string $dir): string
    {
        $filesystem = $this->filesystem;
        $name = explode('.', $file->getClientOriginalName())[0].'-'.random_int(0, mt_getrandmax());
        $path = sprintf('/%s/%s.%s', $dir, $name, $file->getClientOriginalExtension());
        $url = '';

        $original = Utils::tryFopen($file->getRealPath(), 'rb');
        $filesystem->writeStream($path, $original, ['directory_visibility' => 'public', 'visibility' => 'public']);

        if (null !== $this->attachmentUrlPrefix && '' !== $this->attachmentUrlPrefix) {
            $url = $this->attachmentUrlPrefix;
        }

        if (null !== $this->bucketPrefix && '' !== $this->bucketPrefix) {
            $url .= '/'.$this->bucketPrefix;
        }

        return $url.$path;
    }
}
