<?php

namespace App\Service;

use App\Entity\Framework\IdentifiableInterface;
use App\Entity\Framework\Package;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;

readonly class IdentifiableObjectHelper
{
    public function __construct(private ManagerRegistry $registry)
    {
    }

    public function findObjectByIdentifier(string $identifier): ?IdentifiableInterface
    {
        try {
            $uuid = Uuid::fromString($identifier);
        } catch (InvalidUuidStringException) {
            return null;
        }

        $objectTypes = array_keys(Api1RouteMap::$routeMap);

        foreach ($objectTypes as $objectType) {
            if (Package::class === $objectType) {
                continue;
            }

            /** @var ?IdentifiableInterface $obj */
            $obj = $this->registry->getRepository($objectType)->findOneBy(['identifier' => $uuid]);
            if (null !== $obj) {
                return $obj;
            }
        }

        return null;
    }

    public function findObjectByUri(string $uri): ?IdentifiableInterface
    {
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
