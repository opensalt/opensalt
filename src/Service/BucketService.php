<?php

namespace App\Service;

use League\Flysystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BucketService
{
    private $filesystem;
    private $cloudfrontDomain;

    public function __construct(Filesystem $filesyste, string $cloudfrontDomain) {
        $this->filesystem = $filesystem;
        $thiz->cloudfrontDomain = $cloudfrontDomain;
    }

    public function uploadFile (UploadedFile $file, string $dir) {
        $filesystem = $this->filesystem;
        $name = explode(".", $file->getClientOriginalName())[0].rand();
        $path = "/$dir/$name.".$file->getClientOriginalExtension();

        $stream = fopen($file->getRealPath(), 'r+');
        $filesystem->writeStream($path, $stream, ['visibility' => 'public']);
        fclose($stream);

        return $this->cloudfrontDomain.$path;
    }
}
