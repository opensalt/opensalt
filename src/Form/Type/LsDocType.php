<?php

namespace App\Form\Type;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;

class LsDocType extends AbstractLsDocCreateType
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(EntityManagerInterface $em, AuthorizationCheckerInterface $authorizationChecker) {
        parent::__construct($em);
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addOwnership(FormBuilderInterface $builder): void
    {
        $builder
            // @todo: These are placeholder, they should be determined upon creation with a choice of Org or User ownership
            ->add('org', EntityType::class, [
                'required' => false,
                'disabled' => $this->authorizationChecker->isGranted('ROLE_ADMIN') ? false : true,
                // 'disabled' => true,
                'placeholder' => 'None',
                'label' => 'Owning Organization',
                'class' => 'App\Entity\User\Organization',
                'choice_label' => 'name',
            ])
            ->add('user', EntityType::class, [
                'required' => false,
                'disabled' => true,
                'placeholder' => 'None',
                'label' => 'Owning User',
                'class' => 'App\Entity\User\User',
                'choice_label' => 'username',
            ])
        ;
    }
}
