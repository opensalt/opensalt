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
use App\Command\Framework\DeleteFileCommand;
use App\Entity\Framework\LsItem;
use Symfony\Component\HttpFoundation\JsonResponse;

class AwsStorageController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * @Route("/cfdoc/{id}/{field}/aws", name="aws_storage_file")
     *
     */
    public function awsStorage(Request $request, LsItem $lsItem, string $field)
    {
        $output = array('uploaded' => false);
        $filesystem = $this->configuration();
        $file = $request->files->get('file');
        
        $fileName = $file->getClientOriginalName(); 
        $ext = $file->guessExtension();
        $name = explode('.',$fileName);
        $fileName = $name[0].'-'.md5(uniqid()).'.'.$file->getClientOriginalExtension();
        $fileName = preg_replace('/[^A-Za-z0-9\.]/','_',$fileName);
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
      
        $output['uploaded'] = true;
        $output['fileName'] = $fileName;
        return new JsonResponse($output);

    }

    private function configuration()
    {
        $provider = CredentialProvider::defaultProvider();
        $client = \Aws\S3\S3Client::factory([
        'credentials' => [
            'key'    => 'AKIAJMM3WLA2KVT732XA',
            'secret' => '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'
        ], 
       // 'credentials' => $provider,
       // 'region'  => 'us-east-1',
        'region'  => 'eu-central-1',
        'version' => 'latest',
        ]); 
        $adapter    = new AwsS3Adapter($client, "sample-test4");
        //$adapter    = new AwsS3Adapter($client, "actinc.opensalt.np", "dev");
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
       // public function awsDownload(String $fileName):StreamedResponse
        public function awsDownload(String $fileName)
        {
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $exts = array('mp3','mp4','mpeg','mpg','wav'); 
            
            $arr=array();
           // return $this->render('framework/ls_item/show.attachment.twig', $arr);  
            
                $filesystem = $this->configuration();
            $stream = $filesystem->readStream($fileName);
            $contents = stream_get_contents($stream);
           
            if(in_array($ext, $exts)){
                $arr=array('contents'=>base64_encode($contents),'ext'=>$ext);
                return $this->render('framework/ls_item/show.attachment.twig', $arr);  
            }
            else{
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
                ]);
                
            }
           /* $filesystem = $this->configuration();
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
            );*/
            
        }
        
    /**
     * delete attachment.
     *
     * @Route("/cfdoc/{id}/deleteAttachment", name="aws_delete_file")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @param LsItem $lsItem
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */

    public function deleteAttachmentAction(Request $request, LsItem $lsItem)
    {
        $output = array('uploaded' => false);
        $fileName = $request->get('name');
       // $fileName='SampleAudio_0-2d108903326448efa678e681a4818c92.mp3';
        $command=new DeleteFileCommand($lsItem,$fileName);
        $this->sendCommand($command);
        $output['delete'] = true;
        return new JsonResponse($output);
    }        
}