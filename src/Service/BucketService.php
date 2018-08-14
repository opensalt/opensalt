<?php

namespace App\Service;

use League\Flysystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BucketService
{
    private $filesystem;
    private $attachmentUrlPrefix;

    public function __construct(Filesystem $filesystem, ?string $attachmentUrlPrefix)
    {
        $this->filesystem = $filesystem;
        $this->attachmentUrlPrefix = $attachmentUrlPrefix;
    }

    public function uploadFile(UploadedFile $file, string $dir): string
    {
        $filesystem = $this->filesystem;
        $name = explode('.', $file->getClientOriginalName())[0].'-'.rand();
        $path = "/$dir/$name.".$file->getClientOriginalExtension();

        $stream = fopen($file->getRealPath(), 'rb+');
        $filesystem->writeStream($path, $stream, ['visibility' => 'public']);
        fclose($stream);

        return ($this->attachmentUrlPrefix ?? '').$path;
    }
}
