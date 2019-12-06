<?php

namespace App\Service;

use App\Entity\Framework\AbstractLsBase;
use App\Entity\Framework\Package;
use Doctrine\Common\Persistence\ManagerRegistry;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;

class IdentifiableObjectHelper
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function findObjectByIdentifier(string $identifier): ?AbstractLsBase
    {
        try {
            $uuid = Uuid::fromString($identifier);
        } catch (InvalidUuidStringException $e) {
            return null;
        }

        $objectTypes = array_keys(Api1RouteMap::$routeMap);

        foreach ($objectTypes as $objectType) {
            if (Package::class === $objectType) {
                continue;
            }

            /** @var AbstractLsBase $obj */
            $obj = $this->registry->getRepository($objectType)->findOneBy(['identifier' => $uuid]);
            if (null !== $obj) {
                return $obj;
            }
        }

        return null;
    }

    public function findObjectByUri(string $uri): ?AbstractLsBase
    {
        $objectTypes = array_keys(Api1RouteMap::$routeMap);

        foreach ($objectTypes as $objectType) {
            if (Package::class === $objectType) {
                continue;
            }

            /** @var AbstractLsBase $obj */
            $obj = $this->registry->getRepository($objectType)->findOneBy(['uri' => $uri]);
            if (null !== $obj) {
                return $obj;
            }
        }

        return null;
    }
}
