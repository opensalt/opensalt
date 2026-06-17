<?php

declare(strict_types=1);

namespace App\Service;

use App\DataTransformer\CaseJson\PackageTransformer;
use App\DTO\CaseJson\CFPackage;
use App\Entity\Framework\LsDoc;
use App\Util\Collection;
use Symfony\Component\Serializer\SerializerInterface;

class CaseImport
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly PackageTransformer $packageTransformer,
        private readonly SchemaProvider $schemaProvider,
    ) {
    }

    public function importCaseFile(string $content): LsDoc
    {
        ini_set('memory_limit', '4096M');
        set_time_limit(900); // increase time limit for large files

        // Normalize empty strings to null so nullable fields are treated consistently
        $content = $this->normalizeContent($content);

        try {
            $this->validate($content);
        } catch (\Throwable $throwable) {
            // Try to fix things based on common issues seen
            try {
                $newContent = $this->fixupContent($content);
                $this->validate($newContent);
                $content = $newContent;
            } catch (\Throwable) {
                // Ignore error and throw original
                throw $throwable;
            }
        }

        /** @var CFPackage $package */
        $package = $this->serializer->deserialize($content, CFPackage::class, 'json');

        return $this->packageTransformer->transform($package);
    }

    private function validate(string $content): void
    {
        $this->schemaProvider->getCaseV1p1Schema()->in(json5_decode($content));
    }

    /**
     * Normalize the JSON content by removing keys with empty string values.
     *
     * Rather than converting "" to null (which still fails schema validation for
     * fields like officialSourceURL that expect a URI format), we remove the key
     * entirely. This treats the empty string as "not provided", which passes
     * schema validation for all optional fields regardless of their type constraints.
     */
    private function normalizeContent(string $content): string
    {
        // Decode with objects preserved (assoc=false) so empty JSON objects such as
        // "extensions": {} round-trip back to {} instead of becoming [] (which would
        // fail schema validation for object-typed fields like Extensions).
        $data = json5_decode($content, false);
        $data = Collection::removeEmptyStrings($data);
        $data = Collection::stripUnsupportedExtensions($data);

        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    private function fixupContent(string $content): string
    {
        $json = json5_decode($content, true);

        $teks = 1 === preg_match('/teks-api/', $json['CFDocument']['uri']);

        $items = [];
        foreach (($json['CFItems'] ?? []) as $key => $item) {
            // Some TX frameworks have a bad URI for items where it is missing the / after CFItems
            if ($teks && 1 === preg_match('/CFItems[0-9a-fA-F]/', $item['uri'])) {
                $item['uri'] = preg_replace('/CFItems/', 'CFItems/', $item['uri']);
                $json['CFItems'][$key]['uri'] = $item['uri'];
            }

            // Save URIs for items
            if (isset($item['identifier']) && isset($item['uri'])) {
                $items[$item['identifier']] = $item['uri'];
            }

            // Some TX frameworks are missing fullStatement but have alternativeLabel
            if (empty($item['fullStatement']) && (!empty($item['alternativeLabel']) || !empty($item['abbreviatedStatement']))) {
                $json['CFItems'][$key]['fullStatement'] = $item['alternativeLabel'] ?? $item['abbreviatedStatement'];
            }

            // Some Satchel items for Georgia have an empty array for extensions
            if ([] === ($item['extensions'] ?? '')) {
                unset($json['CFItems'][$key]['extensions']);
            }

            // Some Satchel items for Georgia have a bad CFItemTypeURI (not a LinkURI)
            if ('*CLEAR*' === ($item['CFItemTypeURI'] ?? '')) {
                unset($json['CFItems'][$key]['CFItemTypeURI']);
            }

            // Some Satchel frameworks for Georgia have an invalid statusStartDate format
            if (1 === preg_match('!^(\d{1,2})/(\d{1,2})/(\d{2,4})$!', $item['statusStartDate'] ?? '', $matches)) {
                if (2 === strlen($matches[3])) {
                    $matches[3] = sprintf('20%s', $matches[3]);
                }
                $json['CFItems'][$key]['statusStartDate'] = sprintf('%04d-%02d-%02d', $matches[3], $matches[1], $matches[2]);
            }
        }

        // Try fixing up for issue we have seen in the CASE Network where the URI is the identifier instead of a URI
        // but the item has the URI available
        foreach (($json['CFAssociations'] ?? []) as $key => $association) {
            $node = $association['originNodeURI'];
            if ($node['identifier'] === $node['uri']) {
                $json['CFAssociations'][$key]['originNodeURI']['uri'] = $items[$node['identifier']] ?? $node['uri'];
            }

            $node = $association['destinationNodeURI'];
            if ($node['identifier'] === $node['uri']) {
                $json['CFAssociations'][$key]['destinationNodeURI']['uri'] = $items[$node['identifier']] ?? $node['uri'];
            }

            // Fixup for a couple frameworks we have seen that have bad association types
            if ('RelatedTo' === $association['associationType']) {
                $json['CFAssociations'][$key]['associationType'] = 'isRelatedTo';
            }
        }

        // Fixup for some frameworks from mt.satchelcommons.com where the hierarchyCode is an integer instead of a string
        foreach (($json['CFDefinitions']['CFItemTypes'] ?? []) as $key => $itemType) {
            if (is_int($itemType['hierarchyCode'] ?? null) || is_float($itemType['hierarchyCode'] ?? null)) {
                $json['CFDefinitions']['CFItemTypes'][$key]['hierarchyCode'] = (string) $itemType['hierarchyCode'];
            }
        }

        return json_encode($json, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
