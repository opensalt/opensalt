<?php

namespace App\Service;

use App\DataTransformer\CaseJson\PackageTransformer;
use App\DTO\CaseJson\CFPackage;
use App\Entity\Framework\LsDoc;
use Swaggest\JsonSchema\Schema;
use Symfony\Component\Serializer\SerializerInterface;

class CaseImport
{
    public function __construct(
        private SerializerInterface $serializer,
        private PackageTransformer $packageTransformer,
    ) {
    }

    public function importCaseFile(string $content): LsDoc
    {
        ini_set('memory_limit', '2G');
        set_time_limit(900); // increase time limit for large files

        try {
            $this->validate($content);
        } catch (\Exception $e) {
            throw $e;
        }

        /** @var CFPackage $package */
        $package = $this->serializer->deserialize($content, CFPackage::class, 'json');

        return $this->packageTransformer->transform($package);
    }

    private function validate(string $content): void
    {
        $schema = Schema::import(json5_decode(file_get_contents(__DIR__.'/../../config/schema/case-v1p0-cfpackage-schema.json')));
        $schema->in(json5_decode($content));
        $schema = null;
    }
}
