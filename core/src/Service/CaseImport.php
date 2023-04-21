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
        private readonly SerializerInterface $serializer,
        private readonly PackageTransformer $packageTransformer,
    ) {
    }

    public function importCaseFile(string $content): LsDoc
    {
        ini_set('memory_limit', '2G');
        set_time_limit(900); // increase time limit for large files

        try {
            $this->validate($content);
        } catch (\Throwable $e) {
            if (str_starts_with($e->getMessage(), 'Missing scheme in URI')) {
                // Check for common issue seen from CASE Network
                try {
                    $newContent = $this->fixupContent($content);
                    $this->validate($newContent);
                    $content = $newContent;
                } catch (\Throwable $e) {
                    // Ignore error and throw original
                    throw $e;
                }
            } else {
                throw $e;
            }
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

    private function fixupContent(string $content): string
    {
        $json = json5_decode($content, true);

        $items = [];
        foreach (($json['CFItems'] ?? []) as $item) {
            if (isset($item['identifier']) && isset($item['uri'])) {
                $items[$item['identifier']] = $item['uri'];
            }
        }

        // Try fixing up for issue we have seen in the CASE Network where the URI is the identifier instead of a URI
        foreach (($json['CFAssociations'] ?? []) as $key => $association) {
            $node = $association['originNodeURI'];
            if ($node['identifier'] === $node['uri']) {
                $json['CFAssociations'][$key]['originNodeURI']['uri'] = $items[$node['identifier']] ?? $node['uri'];
            }

            $node = $association['destinationNodeURI'];
            if ($node['identifier'] === $node['uri']) {
                $json['CFAssociations'][$key]['destinationNodeURI']['uri'] = $items[$node['identifier']] ?? $node['uri'];
            }
        }

        return json_encode($json, JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
    }
}
