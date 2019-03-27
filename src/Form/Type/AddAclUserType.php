<?php

namespace App\Form\Type;

use App\Entity\Framework\LsDoc;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use App\Entity\User\User;
use App\Form\DTO\AddAclUserDTO;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddAclUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var LsDoc $lsDoc */
        $lsDoc = $options['lsDoc'];

        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'label' => 'Username',
                'placeholder' => '- Choose an editor to exclude -',
                'choice_label' => 'username',
                'query_builder' => function (EntityRepository $er) use ($lsDoc) {
                    $org = $lsDoc->getOrg();
                    if ($org) {
                        $orgId = $org->getId();
                    } else {
                        $orgId = null;
                    }

                    return $er->createQueryBuilder('u')
                        ->leftJoin('u.docAcls', 'acl', Expr\Join::WITH, 'acl.lsDoc = :docId')
                        ->where('u.org = :orgId')
                        ->andWhere('acl.user is null')
                        ->addOrderBy('u.username')
                        ->setParameter('orgId', $orgId)
                        ->setParameter('docId', $lsDoc->getId())
                        ;
                },
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AddAclUserDTO::class,
            'lsDoc' => null,
        ]);
    }
}
