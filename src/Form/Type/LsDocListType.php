<?php

namespace App\Form\Type;

use App\Entity\Framework\LsDoc;
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
                        return mb_substr($val->getTitle(), 0, 59)."\u{2026}";
                    }

                    return $title;
                },
                'group_by' => function(LsDoc $val) {
                    $creator = $val->getCreator();
                    if (strlen($creator) > 60) {
                        return mb_substr($val->getCreator(), 0, 59)."\u{2026}";
                    }

                    return $creator;
                },
                'required' => false,
                'multiple' => false,
                'class' => 'App\Entity\Framework\LsDoc',
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
