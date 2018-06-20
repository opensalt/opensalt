<?php

namespace App\Form\Type;

use App\Entity\Framework\TaskModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extention\Core\Type\SubmitType;
use Symfony\Component\Form\Extention\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskModelType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder->add('text', TextareaType::class, ['label' => false])
            ->add('save', SubmitType::class);
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults([
      'data-class' => TaskModel::class
    ]);
  }
}
