<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Framework\IdentifiableInterface;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\Package;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;

readonly class IdentifiableObjectHelper
{
    public function __construct(private ManagerRegistry $registry)
    {
    }

    public function findObjectByIdentifier(string $identifier, ?string $framework = null): ?IdentifiableInterface
    {
        try {
            $uuid = Uuid::fromString($identifier);
        } catch (InvalidUuidStringException) {
            return null;
        }

        $frameworkUuid = null;
        if (null !== $framework) {
            try {
                $frameworkUuid = Uuid::fromString($framework);
            } catch (InvalidUuidStringException) {
                return null;
            }
        }

        /** @var array<array-key, class-string> $objectTypes */
        $objectTypes = array_keys(Api1RouteMap::$routeMap);

        foreach ($objectTypes as $objectType) {
            if (Package::class === $objectType) {
                continue;
            }

            $query = ['identifier' => $uuid->toString()];
            if (null !== $frameworkUuid && in_array($objectType, [LsItem::class, LsAssociation::class], true)) {
                $query['lsDocIdentifier'] = $frameworkUuid->toString();
            }

            /** @var ?IdentifiableInterface $obj */
            $obj = $this->registry->getRepository($objectType)->findOneBy($query);
            if (null !== $obj) {
                return $obj;
            }
        }

        return null;
    }

    public function findObjectByUri(string $uri): ?IdentifiableInterface
    {
        /** @var array<array-key, class-string> $objectTypes */
        $objectTypes = array_keys(Api1RouteMap::$routeMap);

        foreach ($objectTypes as $objectType) {
            if (Package::class === $objectType) {
                continue;
            }

            /** @var ?IdentifiableInterface $obj */
            $obj = $this->registry->getRepository($objectType)->findOneBy(['uri' => $uri]);
            if (null !== $obj) {
                return $obj;
            }
        }

        return null;
    }
}
