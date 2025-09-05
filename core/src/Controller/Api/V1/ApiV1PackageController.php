<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\DeleteDocumentCommand;
use App\Command\Import\ImportCaseJsonCommand;
use App\Controller\Api\UriController;
use App\DTO\CaseJson\CFPackage;
use App\Entity\Framework\LsDoc;
use App\Entity\User\User;
use App\Security\Permission;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Security(name: 'Bearer')]
#[OA\Response(
    response: 401,
    description: 'The token is not valid',
)]
#[OA\Response(
    response: 403,
    description: 'The token does not have access to the item',
)]
#[OA\Response(
    response: 404,
    description: 'The package cannot be found',
)]
#[OA\Tag('Package', description: 'Operations on CASE v1.1 CF Packages')]
class ApiV1PackageController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/v1/packages/{documentIdentifier}', name: 'api_v1_package_get', methods: ['GET'])]
    #[IsGranted(Permission::FRAMEWORK_VIEW, 'doc')]
    #[OA\Get(
        operationId: 'api_v1_package_get',
        description: 'Get a single package with full structure',
        summary: 'Get framework package',
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The complete package structure',
        content: new Model(type: CFPackage::class),
    )]
    public function getPackage(
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
    ): Response {
        return $this->forward(UriController::class.'::findUriSingleIdentifier', [
            'uri' => 'p'.$doc->getIdentifier(),
            '_format' => 'json',
        ]);
    }

    #[Route('/api/v1/packages', methods: ['POST'])]
    #[IsGranted(Permission::FRAMEWORK_CREATE)]
    #[OA\Post(
        operationId: 'api_v1_package_post',
        description: 'Create a new complete package with all components',
        summary: 'Create package',
    )]
    #[OA\RequestBody(content: new Model(type: CFPackage::class))]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Package updated successfully',
        headers: [
            new OA\Header(
                header: 'Location',
                description: 'Location of the updated package',
                schema: new OA\Schema(type: 'string', format: 'uri')
            ),
        ]
    )]
    #[OA\Response(
        response: Response::HTTP_UNPROCESSABLE_ENTITY,
        description: 'There is an error in the package payload',
    )]
    public function postPackage(
        #[MapRequestPayload()] CFPackage $packageDto,
        Request $request,
        #[CurrentUser] ?User $user = null,
    ): Response {
        $errors = $this->validator->validate($packageDto);
        if (\count($errors) > 0) {
            $showErrors = [];
            foreach ($errors as $error) {
                $showErrors[] = $error->getMessage();
            }

            throw new UnprocessableEntityHttpException(implode(', ', $showErrors));
        }

        $command = new ImportCaseJsonCommand($request->getContent(), $user?->getOrg(), $user);
        try {
            $this->sendCommand($command);
        } catch (\Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }

        return new Response(null, Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('api_v1_package_get', ['documentIdentifier' => $packageDto->cfDocument->identifier]),
        ]);
    }

    #[Route('/api/v1/packages/{documentIdentifier}', methods: ['PUT'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'doc')]
    #[OA\Put(
        operationId: 'api_v1_package_put',
        description: 'Replace an existing complete package',
        summary: 'Replace package',
    )]
    #[OA\RequestBody(content: new Model(type: CFPackage::class))]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Package updated successfully',
        headers: [
            new OA\Header(
                header: 'Location',
                description: 'Location of the updated package',
                schema: new OA\Schema(type: 'string', format: 'uri')
            ),
        ]
    )]
    #[OA\Response(
        response: Response::HTTP_UNPROCESSABLE_ENTITY,
        description: 'There is an error in the package payload',
    )]
    public function putPackage(
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
        #[MapRequestPayload()] CFPackage $packageDto,
        Request $request,
        #[CurrentUser] ?User $user,
    ): Response {
        $errors = $this->validator->validate($packageDto);
        if (\count($errors) > 0) {
            $showErrors = [];
            foreach ($errors as $error) {
                $showErrors[] = $error->getMessage();
            }

            throw new UnprocessableEntityHttpException(implode(', ', $showErrors));
        }

        $command = new ImportCaseJsonCommand($request->getContent(), $user?->getOrg(), $user);
        try {
            $this->sendCommand($command);
        } catch (\Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }

        return new Response(null, Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('api_v1_package_get', ['documentIdentifier' => $packageDto->cfDocument->identifier]),
        ]);
    }

    #[Route('/api/v1/packages/{documentIdentifier}', methods: ['DELETE'])]
    #[IsGranted(Permission::FRAMEWORK_DELETE, 'doc')]
    #[OA\Delete(
        operationId: 'api_v1_package_delete',
        description: 'Delete a complete package and all related entities',
        summary: 'Delete package',
    )]
    #[OA\Response(
        response: Response::HTTP_NO_CONTENT,
        description: 'The package and all related entities have been deleted',
    )]
    public function deletePackage(
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
    ): Response {
        $command = new DeleteDocumentCommand($doc);
        $this->sendCommand($command);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
