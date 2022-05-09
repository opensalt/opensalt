<?php

namespace App\Controller\Mirror;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\Mirror\Framework;
use App\Entity\Framework\Mirror\Log;
use App\Form\DTO\MirroredFrameworkDTO;
use App\Form\Type\MirroredFrameworkDTOType;
use App\Service\MirrorServer;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/mirror/framework')]
#[Security("is_granted('manage', 'mirrors')")]
class FrameworkController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    #[Route(path: '/new', name: 'mirror_framework_new')]
    public function new(Request $request, MirrorServer $mirrorService): Response
    {
        $frameworkDto = new MirroredFrameworkDTO();
        $form = $this->createForm(MirroredFrameworkDTOType::class, $frameworkDto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $framework = $mirrorService->addSingleFramework($frameworkDto);
                $server = $framework->getServer();

                return $this->redirectToRoute('mirror_server_list', ['id' => $server->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('mirror/framework/new.html.twig', [
            'mirrored_framework' => $frameworkDto,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/resolve-id-conflict', name: 'mirror_framework_resolve_conflict')]
    public function resolveConflict(Request $request, Framework $framework): Response
    {
        $em = $this->managerRegistry->getManager();

        $doc = $em->getRepository(LsDoc::class)->findOneByIdentifier($framework->getIdentifier());
        if (null === $doc) {
            $this->addFlash('error', 'There is no conflict.');

            return $this->redirectToRoute('mirror_server_list', ['id' => $framework->getServer()->getId()]);
        }

        $resolveForm = $this->createFrameworkResolveForm($framework);
        $resolveForm->handleRequest($request);

        if ($resolveForm->isSubmitted() && $resolveForm->isValid()) {
            $prevFramework = $doc->getMirroredFramework();
            if (null !== $prevFramework) {
                $prevFramework->setStatus(Framework::STATUS_ERROR);
                $prevFramework->markFailure(Framework::ERROR_ID_CONFLICT);
                $prevFramework->setInclude(false);
                $prevFramework->addLog(Log::STATUS_FAILURE, 'Another framework with the same identifier has been selected to be the mirror');
            }

            $doc->setMirroredFramework($framework);
            $doc->setOrg(null);
            $doc->setUser(null);
            $framework->setInclude(true);
            $framework->markToRefresh();

            foreach ($doc->getDocAcls() as $acl) {
                $em->remove($acl);
            }

            $em->flush();

            return $this->redirectToRoute('mirror_server_list', ['id' => $framework->getServer()->getId()]);
        }

        return $this->render('mirror/framework/resolve.html.twig', [
            'frameworkToMirror' => $framework,
            'currentFramework' => $doc,
            'resolveForm' => $resolveForm->createView(),
        ]);
    }

    #[Route(path: '/{id}/refresh', name: 'mirror_framework_refresh', methods: ['POST'])]
    public function refresh(Framework $framework): Response
    {
        $framework->markToRefresh();
        $this->managerRegistry->getManager()->flush();

        return $this->redirectToRoute('mirror_server_list', ['id' => $framework->getServer()->getId()]);
    }

    #[Route(path: '/{id}/enable', name: 'mirror_framework_enable', methods: ['POST'])]
    public function enable(Framework $framework): Response
    {
        $framework->setInclude(true);
        $framework->setStatus(Framework::STATUS_NEW);
        $framework->setErrorType(null);
        $framework->addLog(
            Log::STATUS_SUCCESS,
            'Mirroring was enabled'
        );
        $framework->markToRefresh();
        $this->managerRegistry->getManager()->flush();

        return $this->redirectToRoute('mirror_server_list', ['id' => $framework->getServer()->getId()]);
    }

    #[Route(path: '/{id}/disable', name: 'mirror_framework_disable', methods: ['POST'])]
    public function disable(Framework $framework): Response
    {
        $framework->setInclude(false);
        if (null !== $framework->getFramework()) {
            $framework->getFramework()->setMirroredFramework(null);
            $framework->setStatus(Framework::STATUS_ERROR);
            $framework->setErrorType(Framework::ERROR_ID_CONFLICT);
            $framework->addLog(
                Log::STATUS_SUCCESS,
                'Mirroring was disabled, a framework now exists on the server with the same identifier'
            );
        }
        $this->managerRegistry->getManager()->flush();

        return $this->redirectToRoute('mirror_server_list', ['id' => $framework->getServer()->getId()]);
    }

    #[Route(path: '/{id}/logs', name: 'mirror_framework_logs')]
    public function viewLog(Framework $framework): Response
    {
        return $this->render('mirror/framework/logs.html.twig', [
            'framework' => $framework,
        ]);
    }

    private function createFrameworkResolveForm(Framework $framework): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('mirror_framework_resolve_conflict', ['id' => $framework->getId()]))
            ->getForm()
            ;
    }
}
