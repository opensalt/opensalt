<?php

namespace App\Controller;

use App\Security\Permission;
use App\Service\SubtypeUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\File;

#[Route('/subtype/update')]
class SubtypeUpdateController extends AbstractController
{
    public function __construct(private readonly SubtypeUpdater $updater)
    {
    }

    #[Route('/', name: 'association_subtype_update')]
    #[IsGranted(Permission::FRAMEWORK_EDIT_ALL)]
    public function index(Request $request): Response
    {
        $form = $this->createFormBuilder()
            ->add('file', FileType::class, [
                'label' => 'File with association subtypes',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                    ]),
                ],
            ])
            ->getForm()
        ;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\File $file */
            $file = $form->get('file')->getData();

            $saveFilename = uniqid('SubtypeUpdate-', true).'.'.$file->guessExtension();
            $path = $file->move('/tmp/', $saveFilename);
            try {
                $output = $this->updater->loadSpreadsheet($path);
            } catch (\Exception) {
                unlink($path);

                throw new BadRequestException('An error occurred while loading the spreadsheet.');
            }

            return $this->render('subtype_update/output.html.twig', [
                'result' => $output,
            ]);
        }

        return $this->render('subtype_update/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
