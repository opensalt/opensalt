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
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Salt\UserBundle\Entity\User;

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
            ->add('username', TextType::class, [
                'attr' => ['class' => 'form-control'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'required' => in_array('registration', $options['validation_groups'], true),
                'first_options' => array('label' => 'Password'),
                'second_options' => array('label' => 'Confirm Password'),
            ])
            ->add('org', EntityType::class, [
                'label' => 'Organization',
                'class' => 'Salt\UserBundle\Entity\Organization',
                'choice_label' => 'name',
            ])
            ->add('new_org', TextType::class, [
                'label' => 'New Organization',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'new-org-field'],
            ])
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();

                if (is_null($data['org'])) {
                    unset($data['org']);
                    $event->setData($data);
                }
            });
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

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $newOrg = new ChoiceView([], 'other', 'Other');
        $view->children['org']->vars['choices'][] = $newOrg;
    }
}
