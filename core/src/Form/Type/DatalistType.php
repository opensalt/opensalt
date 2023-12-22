<?php

namespace App\Form\Type;

use App\Entity\Framework\FrameworkType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;

/**
 * @extends AbstractType<FrameworkType>
 */
class DatalistType extends AbstractType
{
    public function getParent(): ?string
    {
        return EntityType::class;
    }
}
