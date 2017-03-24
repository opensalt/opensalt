<?php

namespace CftfBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LsDefAssociationGroupingType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('lsDoc', EntityType::class, [
                    'class' => 'CftfBundle:LsDoc',
                    'choice_label' => 'title',
                    'group_by' => 'creator',
                    'required' => false,
                    'multiple' => false,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('i')
                            ->orderBy('i.title', 'ASC');
                    },
                ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CftfBundle\Entity\LsDefAssociationGrouping'
        ));
    }
}
