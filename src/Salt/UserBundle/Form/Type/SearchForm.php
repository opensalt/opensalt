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
            ])
            ->add('user_role', ChoiceType::class, [
              'choices' => [
                'Super User' => '["ROLE_SUPER_USER"]',
                'Super Editor' => '["ROLE_SUPER_EDITOR"]',
                'Admin' => '["ROLE_ADMIN"]',
                'Editor' => '["ROLE_EDITOR"]',
                'User' => '[]',
              ],
              'required' => false,
        ]);
    }

}
