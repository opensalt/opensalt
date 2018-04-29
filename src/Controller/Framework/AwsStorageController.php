<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Service\ExcelExport;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use League\Flysystem\Adapter\AwsS3 as Adapter;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Aws\Credentials\CredentialProvider;
use App\Command\Framework\AddFileToAwsCommand;

class AwsStorageController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * @Route("/cfdoc/{id}/{field}/aws", name="aws_storage_file")
     *
     */
    public function awsStorage(Request $request, LsItem $lsItem, string $field)
    {
        $filesystem = $this->configuration();
        
        $file = $request->files->get('file');
        $fileName = $file->getClientOriginalName(); 
        $filePath = $file->getRealPath(); 
        if (!$filesystem->has($fileName))
        {
            $stream = fopen($filePath, 'r+');
            $result =$filesystem->writeStream($fileName, $stream);
            fclose($stream);
            echo '<div class="message">File Uploaded Successfully..!!</div>';
        }
        $command=new AddFileToAwsCommand($lsItem, $fileName, $field);
        $this->sendCommand($command);
        return "File Saved.";
    }

    private function configuration()
    {
        $provider = CredentialProvider::defaultProvider();
        $client = \Aws\S3\S3Client::factory([
        /*'credentials' => [
            'key'    => 'AKIAJMM3WLA2KVT732XA',
            'secret' => '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'
        ], */
        'credentials' => $provider,
        'region'  => 'us-east-1',
        //'region'  => 'eu-central-1',
        'version' => 'latest',
        ]); 

        //$adapter    = new AwsS3Adapter($client, "sample-test4");
        $adapter    = new AwsS3Adapter($client, "actinc.opensalt.np", "dev");
        $filesystem = new Filesystem($adapter);
        return $filesystem;
    }

/**
 * @param String $fileName
 *
 * @Route("/{fileName}/file-download", requirements={"fileName"=".+"}, name="aws_file_download")
 * @return StreamedResponse
 *
 */
    public function awsDownload(String $fileName): StreamedResponse
    {
        $filesystem = $this->configuration();
        $stream = $filesystem->readStream($fileName);
        $contents = stream_get_contents($stream);

        return new StreamedResponse(
            function () use ($contents, $fileName, $filesystem) {
                $local = fopen('php://output', 'rw+');
                fwrite($local, $contents);
                fclose($local);
            },
            200,
            [
                'Content-Type' => $filesystem->getMimetype($fileName),
                'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }
}