<?php

namespace App\Service;

use Aws\S3\S3Client;
use League\Flysystem\AdapterInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Adapter\Local;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BucketFactory
{
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function filesystem(): AdapterInterface
    {
        if ('S3' === $this->params->get('bucket_provider')) {
            $client = new S3Client([
                'credentials' => [
                    'key' => $this->params->get('aws_key'),
                    'secret' => $this->params->get('aws_secret'),
                ],
                'region' => $this->params->get('aws_region'),
                'version' => 'latest'
            ]);

            return new AwsS3Adapter($client, $this->params->get('aws_bucket'), $this->params->get('aws_prefix'));
        }

        $path = $this->params->get('local_filesystem_path');

        return new Local($path);
    }
}
