<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;

/**
 * LsDefGrade
 *
 * @ORM\Table(name="ls_def_grade")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\LsDefGradeRepository")
 */
class LsDefGrade extends AbstractLsDefinition
{
    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255)
     */
    private $code;

    /**
     * @var int
     *
     * @ORM\Column(name="rank", type="integer", nullable=true)
     */
    private $rank;


    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return LsDefGrade
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return int
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * @param int $rank
     *
     * @return LsDefGrade
     */
    public function setRank($rank)
    {
        $this->rank = $rank;
        return $this;
    }

    public function getLabel()
    {
        return "{$this->getCode()} - {$this->getTitle()}";
    }
}
