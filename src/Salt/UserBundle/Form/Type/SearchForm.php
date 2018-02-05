<?php

namespace Salt\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SearchForm
 */
class SearchForm extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('organization', TextType::class, [
              'label' => 'Organization',
              'required' => false,
              'attr' => ['autocomplete' => 'off'],
            ])
            ->add('user_role', ChoiceType::class, [
              'choices' => [
                'Super User' => 'Super User',
                'Super Editor' => 'Super Editor',
                'Admin' => 'Admin',
                'Editor' => 'Editor',
                'User' => 'User',
              ],
              'required' => false,
        ]);
    }

}
