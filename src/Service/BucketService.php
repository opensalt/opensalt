<?php

namespace App\Service;

use League\Flysystem\Filesystem;

class BucketService
{
    private $filesystem;

    public function __construct(Filesystem $filesystem) {
        $this->filesystem = $filesystem;
    }

    public function uploadFile ($file, $dir) {
        $filesystem = $this->filesystem;
        $path = "/$dir/".$file->getClientOriginalName();

        $stream = fopen($file->getRealPath(), 'r+');
        $filesystem->writeStream($path, $stream, ['visibility' => 'public']);
        fclose($stream);

        return $path;
    }

    public function retrieveFile ($path) {
        $filesystem = $this->filesystem;

        $stream = $filesystem->readStream($path);
        $contents = stream_get_contents($stream);
        fclose($stream);
    }

    public function listContent ($path) {
        $filesystem = $this->filesystem;

        $contents = $filesystem->listContents($path, $recursive);
    }
}
