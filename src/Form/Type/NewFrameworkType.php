<?php

namespace App\Form\Type;

use App\Command\Framework\AddDocumentCommand;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewFrameworkType extends AbstractType implements DataMapperInterface
{
//    private $command;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
//        $this->command = $options['command'];

        $builder
            ->setDataMapper($this)
            ->add('creator', null, [
                'required' => true,
            ])
            ->add('title', null, [
                'required' => true,
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
//        $resolver->setRequired('command');

        $resolver->setDefaults([
            'data_class' => AddDocumentCommand::class,
            'ajax' => false,
            'empty_data' => null,
//            'empty_data' => function (FormInterface $form) {
//                return new AddDocumentCommand([
//                    'creator' => $form->get('creator')->getData(),
//                    'title' => $form->get('title')->getData(),
//                ]);
//            },
            //'csrf_protection' => false,
        ]);
    }

    /**
     * Maps properties of some data to a list of forms.
     *
     * @param mixed $data Structured data
     * @param FormInterface[]|\Traversable $forms A list of {@link FormInterface} instances
     *
     * @throws Exception\UnexpectedTypeException if the type of the data parameter is not supported
     */
    public function mapDataToForms($data, $forms)
    {
        $forms = iterator_to_array($forms);
//        $forms['creator']->setData($data ? $data->payload['creator'] ?? '' : '');
//        $forms['title']->setData($data ? $data->payload['title'] ?? '' : '');
        $forms['creator']->setData($data ? $data->creator : '');
        $forms['title']->setData($data ? $data->title : '');
    }

    /**
     * Maps the data of a list of forms into the properties of some data.
     *
     * @param FormInterface[]|\Traversable $forms A list of {@link FormInterface} instances
     * @param mixed $data Structured data
     *
     * @throws Exception\UnexpectedTypeException if the type of the data parameter is not supported
     */
    public function mapFormsToData($forms, &$data)
    {
        $forms = iterator_to_array($forms);
        $data = new AddDocumentCommand([
            'creator' => $forms['creator']->getData(),
            'title' => $forms['title']->getData(),
        ]);
    }
}
