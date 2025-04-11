<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\MappedSuperclass]
class AbstractLsBase implements IdentifiableInterface
{
    use CloneIdentifiableTrait;
    use IdentifiableTrait;
    use ExtraDataTrait;

    public function __construct(string|null $identifier = null)
    {
        if (is_string($identifier) && Uuid::isValid($identifier)) {
            $identifier = strtolower(Uuid::fromString($identifier)->toString());
        } else {
            $identifier = Uuid::uuid1()->toString();
        }

        $this->identifier = $identifier;
        $this->uri = 'local:'.$this->identifier;

        $this->updatedAt = new \DateTimeImmutable();
        $this->changedAt = $this->updatedAt;
    }
}
