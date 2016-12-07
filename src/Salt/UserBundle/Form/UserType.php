<?php

namespace Salt\UserBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [
                //'disabled' => !in_array('registration', $options['validation_groups']),
            ])
            ->add('plainPassword', TextType::class, [
                'required' => in_array('registration', $options['validation_groups']),
                'label' => 'Plain Password',
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Super User' => 'ROLE_SUPER_USER',
                    'Super Editor' => 'ROLE_SUPER_EDITOR',
                    'Organization Admin' => 'ROLE_ADMIN',
                    'Editor' => 'ROLE_EDITOR',
                    //'User' => 'ROLE_USER',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('org', EntityType::class, [
                'class' => 'Salt\UserBundle\Entity\Organization',
                'choice_label' => 'name',
            ])
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Salt\UserBundle\Entity\User',
            'validation_groups' => ['Default'],
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'salt_userbundle_user';
    }
}
