<?php

namespace Pcg\Doctrine\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * NestedSet Trait, usable with PHP >= 5.4.
 *
 * @author Renaat De Muynck <renaat.demuynck@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
trait NestedSetEntity
{
    /**
     * @var int
     * @Gedmo\TreeRoot
     * @ORM\Column(type="integer", nullable=true)
     */
    private $root;

    /**
     * @var int
     * @Gedmo\TreeLevel
     * @ORM\Column(type="integer", name="lvl")
     */
    private $level;

    /**
     * @var int
     * @Gedmo\TreeLeft
     * @ORM\Column(type="integer", name="lft")
     */
    private $left;

    /**
     * @var int
     * @Gedmo\TreeRight
     * @ORM\Column(type="integer", name="rgt")
     */
    private $right;
}
