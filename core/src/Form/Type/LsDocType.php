<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\User\AccessGroup;
use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class LsDocType extends AbstractLsDocCreateType
{
    public function __construct(EntityManagerInterface $em, private readonly AuthorizationCheckerInterface $authorizationChecker)
    {
        parent::__construct($em);
    }

    #[\Override]
    protected function addOwnership(FormBuilderInterface $builder): void
    {
        $builder
            // @todo: These are placeholder, they should be determined upon creation with a choice of Org or User ownership
            ->add('org', EntityType::class, [
                'required' => false,
                'disabled' => !$this->authorizationChecker->isGranted('ROLE_ADMIN'),
                // 'disabled' => true,
                'placeholder' => 'None',
                'label' => 'Owning Access Group',
                'class' => AccessGroup::class,
                'choice_label' => 'name',
            ])
            ->add('user', EntityType::class, [
                'required' => false,
                'disabled' => true,
                'placeholder' => 'None',
                'label' => 'Owning User',
                'class' => User::class,
                'choice_label' => 'username',
            ])
        ;
    }
}
