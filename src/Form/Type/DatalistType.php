<?php

namespace App\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;

class DatalistType extends AbstractType
{
    public function getParent() {
        return EntityType::class;
    }
}
