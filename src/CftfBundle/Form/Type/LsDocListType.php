<?php

namespace CftfBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LsDocListType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('lsDoc', EntityType::class, [
                'label' => 'Choose Document',
                'choice_label' => 'title',
                'group_by' => 'creator',
                'required' => false,
                'multiple' => false,
                'class' => 'CftfBundle\Entity\LsDoc',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('d')
                        ->addOrderBy('d.creator', 'ASC')
                        ->addOrderBy('d.title', 'ASC')
                        ;
                },
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'ajax' => false,
        ]);
    }
}
