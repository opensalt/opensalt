<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LsDefAssociationGroupingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('lsDoc', EntityType::class, [
                    'class' => 'App\Entity\Framework\LsDoc',
                    'choice_label' => 'title',
                    'group_by' => 'creator',
                    'required' => true,
                    'multiple' => false,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('i')
                            ->orderBy('i.title', 'ASC');
                    },
                ])
        ;
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Framework\LsDefAssociationGrouping'
        ));
    }
}
