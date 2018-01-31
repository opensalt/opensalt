<?php

namespace CftfBundle\Form\Type;

use CftfBundle\Entity\LsDoc;
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
                'label' => 'Document:',
                'choice_label' => function(LsDoc $val) {
                    $title = $val->getTitle();
                    if (strlen($title) > 60) {
                        return substr($val->getTitle(), 0, 59)."\u{2026}";
                    }

                    return $title;
                },
                'group_by' => function(LsDoc $val) {
                    $creator = $val->getCreator();
                    if (strlen($creator) > 60) {
                        return substr($val->getCreator(), 0, 59)."\u{2026}";
                    }

                    return $creator;
                },
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
