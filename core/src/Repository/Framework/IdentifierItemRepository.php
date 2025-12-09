<?php

declare(strict_types=1);

namespace App\Repository\Framework;

use App\DTO\ItemType\IdentifierDto;
use App\DTO\ItemType\OrganizationDto;
use App\DTO\ItemType\PublicKeyDto;
use App\Entity\Framework\FrameworkType;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LsItem>
 */
class IdentifierItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LsItem::class);
    }

    public function findIssuerFrameworkType(): ?FrameworkType
    {
        return $this->getEntityManager()->getRepository(FrameworkType::class)
            ->findOneBy(['frameworkType' => 'Issuer Registry']);
    }

    /**
     * @return LsDoc[]
     */
    public function findIssuerFrameworks(): array
    {
        return $this->getEntityManager()->getRepository(LsDoc::class)
            ->findBy([
                'org' => 2,
                'frameworkType' => $this->findIssuerFrameworkType()->getId(),
            ]);
    }

    /**
     * @return LsItem[]
     */
    public function findIssuerIdentifiers(): array
    {
        $issuerFrameworks = $this->findIssuerFrameworks();

        return $this->findBy([
            'discriminator' => LsItemKind::fromDtoClass(IdentifierDto::class)->value,
            'lsDoc' => $issuerFrameworks,
        ]);
    }

    /**
     * @return array<LsItem>
     */
    public function findIssuerItems(): array
    {
        $issuerFrameworks = $this->findIssuerFrameworks();
        $frameworkIds = array_map(fn ($lsDoc): ?int => $lsDoc->getId(), $issuerFrameworks);

        return $this->createQueryBuilder('i')
            ->select('i', 'a', 'd')
            ->join('i.inverseAssociations', 'a')
            ->join('a.originLsItem', 'd')
            ->where('i.discriminator = :issuerDiscriminator')
            ->andWhere('i.lsDoc in (:docs)')
            ->andWhere('a.type = :associationType')
            ->andWhere('d.discriminator = :identifierDiscriminator')
            ->setParameter('issuerDiscriminator', LsItemKind::fromDtoClass(OrganizationDto::class)->value, ParameterType::INTEGER)
            ->setParameter('identifierDiscriminator', LsItemKind::fromDtoClass(IdentifierDto::class)->value, ParameterType::INTEGER)
            ->setParameter('docs', $frameworkIds, ArrayParameterType::INTEGER)
            ->setParameter('associationType', LsAssociation::CHILD_OF)
            ->orderBy('i.abbreviatedStatement')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array{issuer: LsItem, identifiers: IdentifierDto[]}
     */
    public function findIssuerById(int $id): ?array
    {
        $issuer = $this->findOneBy(['id' => $id]);

        if (!$issuer instanceof LsItem) {
            return null;
        }

        $associations = $issuer->getInverseAssociations();
        $identifiers = [];
        foreach ($associations as $association) {
            if (LsItemKind::Identifier->value === $association->getOriginLsItem()?->getDiscriminator()) {
                $identifiers[] = IdentifierDto::fromItem($association->getOriginLsItem());
            }
        }

        return ['issuer' => $issuer, 'identifiers' => $identifiers];
    }

    /**
     * @return array{issuer: LsItem, org: OrganizationDto, keys: PublicKeyDto[]}
     */
    public function findIssuerInfo(string $sub): ?array
    {
        $issuer = $this->findOneBy(['uri' => $sub]);

        if (null === $issuer) {
            return null;
        }

        $assocs = $this->getEntityManager()->getRepository(LsAssociation::class)
            ->findAllAssociationsFor($issuer->getIdentifier());

        // Parent organization
        $parent = null;
        // Child public keys
        $keys = [];

        foreach ($assocs as $assoc) {
            if (LsItemKind::Organization->value === $assoc->getOriginLsItem()?->getDiscriminator()) {
                $parent = OrganizationDto::fromItem($assoc->getOriginLsItem());
            }
            if (LsItemKind::Organization->value === $assoc->getDestinationLsItem()?->getDiscriminator()) {
                $parent = OrganizationDto::fromItem($assoc->getDestinationLsItem());
            }
            if (LsItemKind::PublicKey->value === $assoc->getOriginLsItem()?->getDiscriminator()) {
                $keys[] = PublicKeyDto::fromItem($assoc->getOriginLsItem());
            }
            if (LsItemKind::PublicKey->value === $assoc->getDestinationLsItem()?->getDiscriminator()) {
                $keys[] = PublicKeyDto::fromItem($assoc->getDestinationLsItem());
            }
        }

        return ['issuer' => $issuer, 'org' => $parent, 'keys' => $keys];
    }
}
