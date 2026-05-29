<?php

declare(strict_types=1);

namespace App\DataTransformer\CaseJson;

use App\DTO\CaseJson\CFLicense;
use App\Entity\Framework\LsDefLicence;
use App\Repository\Framework\LsDefLicenceRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class LicencesTransformer
{
    public function __construct(
        private EntityManagerInterface $em,
        private LsDefLicenceRepository $repository,
    ) {
    }

    /**
     * @param CFLicense[] $cfLicences
     *
     * @return LsDefLicence[]
     */
    public function transform(array $cfLicences): array
    {
        if ([] === $cfLicences) {
            return [];
        }

        $licences = $this->findExistingLicences($cfLicences);

        foreach ($cfLicences as $cfLicence) {
            $this->updateLicence($cfLicence, $licences);
        }

        UriCollisionResolver::resolve($licences, $this->repository);

        return $licences;
    }

    /**
     * @param CFLicense[] $cfLicences
     *
     * @return LsDefLicence[]
     */
    private function findExistingLicences(array $cfLicences): array
    {
        $newIds = array_map(static fn (CFLicense $itemType): string => $itemType->identifier->toString(), $cfLicences);

        return $this->repository->findByIdentifiers($newIds);
    }

    /**
     * @param LsDefLicence[] $licences
     */
    private function updateLicence(CFLicense $cfLicence, array &$licences): void
    {
        $licence = $this->findOrCreateLicence($cfLicence, $licences);
        $licence->setUri($cfLicence->uri);
        $licence->setTitle($cfLicence->title);
        $licence->setDescription($cfLicence->description);
        $licence->setChangedAt($cfLicence->lastChangeDateTime);
        $licence->setLicenceText($cfLicence->licenseText);
        $licence->setExtensions($cfLicence->extensions);
    }

    /**
     * @param LsDefLicence[] $licences
     */
    private function findOrCreateLicence(CFLicense $cfLicense, array &$licences): LsDefLicence
    {
        if (!array_key_exists($cfLicense->identifier->toString(), $licences)) {
            $licence = new LsDefLicence($cfLicense->identifier->toString());

            $this->em->persist($licence);
            $licences[$licence->getIdentifier()] = $licence;
        }

        return $licences[$cfLicense->identifier->toString()];
    }
}
