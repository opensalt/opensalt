<?php

declare(strict_types=1);

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\MappedSuperclass]
class AbstractLsBase implements IdentifiableInterface
{
    use IdentifiableTrait;
    use CloneIdentifiableTrait;
    use ExtraDataTrait;
    use ExtensionTrait;

    public function __construct(?string $identifier = null)
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
