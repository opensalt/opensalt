<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="ls_def_association_grouping")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\LsDefAssociationGroupingRepository")
 *
 * @Serializer\ExclusionPolicy("all")
 */
class LsDefAssociationGrouping extends AbstractLsDefinition implements CaseApiInterface
{
    /**
     * @var LsDoc
     *
     * @ORM\ManyToOne(targetEntity="LsDoc", inversedBy="associationGroupings")
     *
     * @Assert\NotNull()
     */
    private $lsDoc;

    /**
     * @return LsDoc
     */
    public function getLsDoc(): ?LsDoc
    {
        return $this->lsDoc;
    }

    /**
     * @param LsDoc $lsDoc
     *
     * @return LsDefAssociationGrouping
     */
    public function setLsDoc(?LsDoc $lsDoc)
    {
        $this->lsDoc = $lsDoc;

        return $this;
    }

    /**
     * Create a duplicate of the lsDefAssociationGrouping into a new document.
     */
    public function duplicateToLsDoc(LsDoc $newLsDoc): LsDefAssociationGrouping
    {
        $newAssociationGrouping = clone $this;
        $newAssociationGrouping->setLsDoc($newLsDoc);
        return $newAssociationGrouping;
    }
}
