<?php

namespace App\Form\Type;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Form\DTO\ChangeLsItemParentDTO;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LsItemParentType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var LsDoc $lsDoc */
        $lsDoc = $options['lsDoc'];

        if ($options['data']) {
            $id = $options['data']->lsItem->getId();
        } else {
            $id = -1;
        }

        $builder
            ->add('parentItem', EntityType::class, [
                'placeholder' => '- None (Top Level in Document) -',
                'label' => 'Choose Parent Statement from Below',
                'choice_label' => 'fullStatement',
                'required' => false,
                'multiple' => false,
                'class' => LsItem::class,
                'query_builder' => function (EntityRepository $er) use ($lsDoc, $id) {
                    return $er->createQueryBuilder('i')
                        ->where('i.lsDoc = :docId')
                        ->andWhere('i.id != :id')
                        ->orderBy('i.fullStatement', 'ASC')
                        ->setParameter('docId', $lsDoc->getId())
                        ->setParameter('id', $id)
                    ;
                },
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChangeLsItemParentDTO::class,
            'ajax' => false,
            'lsDoc' => null,
        ]);
    }
}
