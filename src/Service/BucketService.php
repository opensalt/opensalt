<?php

namespace App\Service;

use GuzzleHttp\Psr7;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BucketService
{
    private Filesystem $filesystem;
    private ?string $attachmentUrlPrefix;
    private ?string $bucketPrefix;

    public function __construct(Filesystem $filesystem, ?string $attachmentUrlPrefix, ?string $bucketPrefix)
    {
        $this->filesystem = $filesystem;
        $this->attachmentUrlPrefix = $attachmentUrlPrefix;
        $this->bucketPrefix = $bucketPrefix;
    }

    public function uploadFile(UploadedFile $file, string $dir): string
    {
        $filesystem = $this->filesystem;
        $name = explode('.', $file->getClientOriginalName())[0].'-'.mt_rand();
        $path = "/$dir/$name.".$file->getClientOriginalExtension();
        $url = '';

        $original = Psr7\Utils::tryFopen($file->getRealPath(), 'rb');
        $filesystem->writeStream($path, $original, ['visibility' => 'public']);

        if (!empty($this->attachmentUrlPrefix)) {
            $url = $this->attachmentUrlPrefix;
        }

        if (!empty($this->bucketPrefix)) {
            $url .= '/'.$this->bucketPrefix;
        }

        return $url.$path;
    }
}
