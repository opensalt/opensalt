<?php
/**
 * Copyright (c) 2017 Public Consulting Group
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Salt\UserBundle\Form\Type;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Class SignupType
 *
 * @DI\Service()
 * @DI\Tag("form.type")
 */
class SignupType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildform(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [])
            ->add('plainPassword', PasswordType::class, [
                'required' => in_array('registration', $options['validation_groups'], true),
                'label' => 'Password',
            ])
            ->add('org', EntityType::class, [
                'class' => 'Salt\UserBundle\Entity\Organization',
                'choice_label' => 'name',
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['Default'],
        ]);
    }
}
