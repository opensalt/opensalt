<?php

namespace App\Service;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

class Aws
{
    private $awsKey;
    private $secret;
    private $bucket;
    private $awsPrefix;
    private $cloudfrontDomain;

    public function __construct(string $awsKey = null, string $secret = null, string $bucket = null, string $prefix = null, string $cloudfrontDomain = null)
    {
        $this->awsKey = $awsKey;
        $this->secret = $secret;
        $this->bucket = $bucket;
        $this->awsPrefix = $prefix;
        $this->cloudfrontDomain = $cloudfrontDomain;
    }

    public function filesystem () {
        $client = new S3Client([
            'credentials' => [
                'key' => $this->awsKey,
                'secret' => $this->secret,
            ],
            'region' => 'us-east-1',
            'version' => 'latest'
        ]);

        $adapter = new AwsS3Adapter($client, $this->bucket, $this->awsPrefix);
        $filesystem = new Filesystem($adapter);

        return $filesystem;
    }

    public function uploadFile ($file, $dir) {
        $filesystem = $this->filesystem();
        $path = '/'.$dir.'/'.$file->getClientOriginalName();

        $stream = fopen($file->getRealPath(), 'r+');
        $filesystem->writeStream($path, $stream, ['visibility' => 'public']);
        fclose($stream);

        return 'https://'.$this->cloudfrontDomain.$this->awsPrefix.$path;
    }

    public function retrieveFile ($path) {
        $filesystem = $this->filesystem();

        $stream = $filesystem->readStream($path);
        $contents = stream_get_contents($stream);
        fclose($stream);
    }

    public function listContent ($path) {
        $filesystem = $this->filesystem();
        $recursive = true;

        $contents = $filesystem->listContents($path, $recursive);
    }
}
