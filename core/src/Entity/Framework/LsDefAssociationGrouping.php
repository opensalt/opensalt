<?php

namespace App\Entity\Framework;

use App\Repository\Framework\LsDefAssociationGroupingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'ls_def_association_grouping')]
#[ORM\Entity(repositoryClass: LsDefAssociationGroupingRepository::class)]
class LsDefAssociationGrouping extends AbstractLsDefinition implements CaseApiInterface
{
    #[ORM\ManyToOne(targetEntity: LsDoc::class, inversedBy: 'associationGroupings')]
    #[Assert\NotNull]
    private ?LsDoc $lsDoc = null;

    public function getLsDoc(): ?LsDoc
    {
        return $this->lsDoc;
    }

    public function setLsDoc(?LsDoc $lsDoc): void
    {
        $this->lsDoc = $lsDoc;
    }

    /**
     * Create a duplicate of the lsDefAssociationGrouping into a new document.
     */
    public function duplicateToLsDoc(LsDoc $newLsDoc): static
    {
        $newAssociationGrouping = clone $this;
        $newAssociationGrouping->setLsDoc($newLsDoc);

        return $newAssociationGrouping;
    }
}
