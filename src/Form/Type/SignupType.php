<?php
/**
 * Copyright (c) 2017 Public Consulting Group
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

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
use App\Entity\User\User;

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
                'label' => 'Username (Email address)',
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Passwords do not match',
                'required' => in_array('registration', $options['validation_groups'], true),
                'first_options' => array(
                    'label' => 'Password',
                    'attr' => [
                        'data-toggle' => 'popover',
                        'data-content' => '
                        <p>Password must be at least 8<br> characters long and must meet<br> password
                    complexity rules<br> requiring at least three<br> of the following:</p>
                        <ul>
                            <li>An uppercase letter</li>
                            <li>A lowercase letter</li>
                            <li>A number</li>
                            <li>A special character</li>
                        </ul>'
                    ]
                ),
                'second_options' => array(
                    'label' => 'Confirm Password',
                    'attr' => [
                        'data-toggle' => 'popover',
                        'data-content' => 'Must match password'
                    ]
                ),
            ])
            ->add('org', EntityType::class, [
                'label' => 'Organization',
                'class' => 'App\Entity\User\Organization',
                'choice_label' => 'name',
                'placeholder' => '- Select Your Organization -',
            ])
            ->add('newOrg', TextType::class, [
                'label' => 'New Organization',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'new-org-field'],
            ])
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();

                if ($data['org'] === 'other' || $data['org'] === '') {
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
        array_unshift($view->children['org']->vars['choices'], $newOrg);
    }
}
