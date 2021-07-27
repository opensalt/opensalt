<?php

namespace App\Service;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BucketFactory
{
    public function __construct(private ParameterBagInterface $params)
    {
    }

    public function filesystem(): FilesystemAdapter
    {
        if ('S3' === $this->params->get('bucket_provider')) {
            $params = [
                'credentials' => [
                    'key' => $this->params->get('aws_key'),
                    'secret' => $this->params->get('aws_secret'),
                ],
                'region' => $this->params->get('aws_region'),
                'version' => 'latest',
            ];

            $allParams = $this->params->all();
            foreach ($allParams as $key => $value) {
                if (str_starts_with($key, 'AWS_S3_OPTION_')) {
                    $params[strtolower(substr($key, 10))] = $value;
                }
            }

            $client = new S3Client($params);

            return new AwsS3V3Adapter($client, (string) $this->params->get('aws_bucket'), (string) $this->params->get('aws_prefix'));
        }

        $path = (string) $this->params->get('local_filesystem_path');

        return new LocalFilesystemAdapter($path);
    }
}
