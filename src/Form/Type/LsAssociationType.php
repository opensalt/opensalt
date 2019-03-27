<?php

namespace App\Form\Type;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LsAssociationType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choiceList = [];
        foreach (LsAssociation::typeChoiceList() as $choice) {
            $choiceList[$choice] = $choice;
        }

        if (!$options['ajax']) {
            $builder
                ->add('uri')
                ->add('originNodeUri')
            ;
        }

        $builder
            ->add('type', ChoiceType::class, [
                'choices' => $choiceList,
                'label' => 'Relationship Type',
            ])
            ->add('lsDoc', EntityType::class, [
                'label' => 'Choose Document',
                'placeholder' => '- Choose a Document or Enter URI Manually -',
                'choice_label' => 'title',
                'required' => false,
                'multiple' => false,
                'class' => LsDoc::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('d')
                        ->orderBy('d.title', 'ASC');
                }
            ])
        ;

        $formModifier = function (FormInterface $form, LsDoc $lsDoc = null) {
            if ($lsDoc) {
                $form->add('destinationLsItem', EntityType::class, [
                    'placeholder' => '- Select Statement From Document Below -',
                    'label' => 'Choose Statement',
                    'choice_label' => 'fullStatement',
                    'required' => TRUE,
                    'multiple' => FALSE,
                    'class' => 'App\Entity\Framework\LsItem',
                    'query_builder' => function (EntityRepository $er) use ($lsDoc) {
                        return $er->createQueryBuilder('d')
                            ->where('d.lsDoc = '.$lsDoc->getId())
                            ->orderBy('d.fullStatement', 'ASC');
                    }
                ]);
            } else {
                $form->add('destinationNodeIdentifier', null, [
                    'label' => 'Destination Id',
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'Enter Identifier',
                    ],
                ]);
                $form->add('destinationNodeUri', null, [
                    'label' => 'Destination Uri',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Enter URI',
                    ],
                ]);
            }
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();

                $formModifier($event->getForm(), $data->getLsDoc());
            }
        );

        $builder->get('lsDoc')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $lsDoc = $event->getForm()->getData();
                $formModifier($event->getForm()->getParent(), $lsDoc);
            }
        );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => LsAssociation::class,
            'ajax' => false,
            //'csrf_protection' => false,
        ));
    }
}
