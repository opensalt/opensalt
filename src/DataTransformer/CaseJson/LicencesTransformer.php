<?php

namespace App\DataTransformer\CaseJson;

use App\DTO\CaseJson\CFLicense;
use App\Entity\Framework\LsDefLicence;
use App\Repository\Framework\LsDefLicenceRepository;
use Doctrine\ORM\EntityManagerInterface;

class LicencesTransformer
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param CFLicense[] $cfLicences
     *
     * @return LsDefLicence[]
     */
    public function transform(array $cfLicences): array
    {
        if (0 === count($cfLicences)) {
            return [];
        }

        $licences = $this->findExistingLicences($cfLicences);

        foreach ($cfLicences as $cfLicence) {
            $this->updateLicence($cfLicence, $licences);
        }

        return $licences;
    }

    /**
     * @param CFLicense[] $cfLicences
     *
     * @return LsDefLicence[]
     */
    protected function findExistingLicences(array $cfLicences): array
    {
        /** @var LsDefLicenceRepository $repo */
        $repo = $this->em->getRepository(LsDefLicence::class);

        $newIds = array_map(static function (CFLicense $itemType) {
            return $itemType->identifier->toString();
        }, $cfLicences);

        return $repo->findByIdentifiers($newIds);
    }

    /**
     * @param LsDefLicence[] $licences
     */
    protected function updateLicence(CFLicense $cfLicence, array &$licences): void
    {
        $licence = $this->findOrCreateLicence($cfLicence, $licences);
        $licence->setUri($cfLicence->uri);
        $licence->setTitle($cfLicence->title);
        $licence->setDescription($cfLicence->description);
        $licence->setChangedAt($cfLicence->lastChangeDateTime);
        $licence->setLicenceText($cfLicence->licenseText);
    }

    /**
     * @param LsDefLicence[] $licences
     */
    protected function findOrCreateLicence(CFLicense $cfLicense, array &$licences): LsDefLicence
    {
        if (!array_key_exists($cfLicense->identifier->toString(), $licences)) {
            $licence = new LsDefLicence($cfLicense->identifier->toString());

            $this->em->persist($licence);
            $licences[$licence->getIdentifier()] = $licence;
        }

        return $licences[$cfLicense->identifier->toString()];
    }
}
