<?php

namespace App\Serializer\CaseJson;

use App\Entity\Framework\IdentifiableInterface;
use App\Entity\Framework\LsDoc;

trait LinkUriTrait
{
    protected function createPackageLinkUri(LsDoc $object, string $type, array $context): ?array
    {
        if (!in_array($type, $context['groups'] ?? [], true)) {
            return null;
        }

        return $this->api1Uris->getLinkUri($object, 'api_v1p0_cfpackage');
    }

    protected function createDocumentLinkUri(LsDoc $object, string $type, array $context): ?array
    {
        if (!in_array($type, $context['groups'] ?? [], true)) {
            return null;
        }

        return $this->createLinkUri($object, $context);
    }

    protected function createLinkUri(?IdentifiableInterface $object, array $context): ?array
    {
        if (null === $object) {
            return null;
        }

        return $this->api1Uris->getLinkUri($object);
    }
}
