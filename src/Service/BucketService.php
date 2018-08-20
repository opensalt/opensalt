<?php

namespace App\Service;

use League\Flysystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use GuzzleHttp\Psr7;

class BucketService
{
    private $filesystem;
    private $attachmentUrlPrefix;
    private $bucketPrefix;

    public function __construct(Filesystem $filesystem, ?string $attachmentUrlPrefix, ?string $bucketPrefix)
    {
        $this->filesystem = $filesystem;
        $this->attachmentUrlPrefix = $attachmentUrlPrefix;
        $this->bucketPrefix = $bucketPrefix;
    }

    public function uploadFile(UploadedFile $file, string $dir): string
    {
        $filesystem = $this->filesystem;
        $name = explode('.', $file->getClientOriginalName())[0].'-'.rand();
        $path = "/$dir/$name.".$file->getClientOriginalExtension();
        $url = '';

        $original = Psr7\stream_for(fopen($file->getRealPath(), 'r'));
        $stream = new Psr7\CachingStream($original);

        $filesystem->write($path, $stream, ['visibility' => 'public']);
        $stream->close();

        if(!empty($this->attachmentUrlPrefix)) {
            $url = $this->attachmentUrlPrefix;
        }

        if(!empty($this->bucketPrefix)) {
            $url .= '/'.$this->bucketPrefix;
        }

        return $url.$path;
    }
}
