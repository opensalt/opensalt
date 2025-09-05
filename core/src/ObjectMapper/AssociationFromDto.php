<?php

namespace App\ObjectMapper;

use App\DTO\Api\V1\AssociationDto;
use App\Entity\Framework\LsAssociation;
use Ramsey\Uuid\Uuid;

readonly class AssociationFromDto
{
    public function __construct(
    ) {
    }

    public static function mapApiV1Association(mixed $value, object $source): LsAssociation
    {
        // $value is the initially created (empty) LsAssociation object
        // $source is the DTO object
        if (!$value instanceof LsAssociation) {
            throw new \InvalidArgumentException('Expecting an Association object to map to');
        }

        if (!$source instanceof AssociationDto) {
            throw new \InvalidArgumentException('Source must be instance of AssociationDto');
        }

        try {
            // See if this is a new object created using `newInstanceWithoutConstructor` or an existing association
            // If we can't get the value due to it being uninitialized then we don't have a fullly instantiated object
            if (!Uuid::isValid($value->getIdentifier())) {
                throw new \InvalidArgumentException('Identifier must be a valid UUID');
            }
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('Expecting an existing Association object to map to', previous: $e);
        }

        if ($source->originNodeURI && $source->originNodeURI->uri) {
            $value->setOrigin($source->originNodeURI->uri, $source->originNodeURI->identifier->toString(), $source->originNodeURI->targetType);
        }
        if ($source->destinationNodeURI && $source->destinationNodeURI->uri) {
            $value->setDestination($source->destinationNodeURI->uri, $source->destinationNodeURI->identifier->toString(), $source->destinationNodeURI->targetType);
        }
        $value->setChangedAt($source->lastChangeDateTime ?? new \DateTimeImmutable());

        // @TODO: find and set the group for the association
        //$value->setGroup();

        return $value;
    }
}
