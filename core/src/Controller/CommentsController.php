<?php

declare(strict_types=1);

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\Comment\AddCommentCommand;
use App\Command\Comment\DeleteCommentCommand;
use App\Command\Comment\DownvoteCommentCommand;
use App\Command\Comment\UpdateCommentCommand;
use App\Command\Comment\UpvoteCommentCommand;
use App\Entity\Comment\Comment;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\User\User;
use App\Security\Permission;
use App\Service\BucketService;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Novaway\Bundle\FeatureFlagBundle\Attribute\FeatureEnabled;
use Novaway\Bundle\FeatureFlagBundle\Manager\FeatureManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[FeatureEnabled(name: 'comments')]
class CommentsController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly FeatureManager $featureManager,
        private readonly SerializerInterface $serializer,
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    #[Route(path: '/comments/document/{id<\d+>}', name: 'create_doc_comment', methods: ['POST'])]
    #[Route(path: '/comments/document/{identifier}', name: 'create_doc_comment_identifier', requirements: ['identifier' => Requirement::UID_RFC4122], methods: ['POST'])]
    #[IsGranted(Permission::COMMENT_ADD)]
    public function newDocComment(
        Request $request,
        #[MapEntity(expr: '((id ?? null) == null) ? repository.findOneByIdentifier(identifier ?? null) : repository.find(id ?? null)')] ?LsDoc $doc,
        #[CurrentUser] User $user,
        BucketService $bucket,
    ): JsonResponse {
        if (null === $doc) {
            return new JsonResponse(['error' => ['message' => 'Document not found']], Response::HTTP_NOT_FOUND);
        }

        return $this->addComment($request, 'document', $doc, $user, $bucket);
    }

    #[Route(path: '/comments/item/{id<\d+>}', name: 'create_item_comment', methods: ['POST'])]
    #[Route(path: '/comments/item/{identifier}', name: 'create_item_comment_identifier', requirements: ['identifier' => Requirement::UID_RFC4122], methods: ['POST'])]
    #[IsGranted(Permission::COMMENT_ADD)]
    public function newItemComment(
        Request $request,
        #[MapEntity(expr: '((id ?? null) == null) ? repository.findOneByIdentifier(identifier ?? null) : repository.find(id ?? null)')] ?LsItem $item,
        #[CurrentUser] User $user,
        BucketService $bucket,
    ): JsonResponse {
        if (null === $item) {
            return new JsonResponse(['error' => ['message' => 'Item not found']], Response::HTTP_NOT_FOUND);
        }

        return $this->addComment($request, 'item', $item, $user, $bucket);
    }

    /**
     * @param Collection<array-key,Comment> $comments
     */
    #[Route(path: '/comments/{itemType<document|item>}/{itemId}', name: 'get_comments', methods: ['GET'])]
    #[IsGranted(Permission::COMMENT_VIEW)]
    public function list(#[MapEntity(class: Comment::class, expr: 'repository.findByTypeId(itemType, itemId)')] Collection $comments, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user instanceof User) {
            foreach ($comments as $comment) {
                $comment->updateStatusForUser($user);
            }
        }

        return $this->apiResponse($comments);
    }

    #[Route(path: '/comments/{id}', methods: ['PUT'])]
    #[IsGranted(Permission::COMMENT_UPDATE, 'comment')]
    public function update(Request $request, Comment $comment): JsonResponse
    {
        $command = new UpdateCommentCommand($comment, $request->request->getString('content'));
        $this->sendCommand($command);

        return $this->apiResponse($comment);
    }

    #[Route(path: '/comments/delete/{id}', methods: ['DELETE'])]
    #[IsGranted(Permission::COMMENT_DELETE, 'comment')]
    public function delete(Comment $comment): JsonResponse
    {
        $command = new DeleteCommentCommand($comment);
        $this->sendCommand($command);

        return $this->apiResponse('Ok', 200);
    }

    #[Route(path: '/comments/{id}/upvote', methods: ['POST'])]
    #[IsGranted(Permission::COMMENT_ADD)]
    public function upvote(Comment $comment, #[CurrentUser] User $user): JsonResponse
    {
        $command = new UpvoteCommentCommand($comment, $user);
        $this->sendCommand($command);

        $this->managerRegistry->getManager()->refresh($comment);
        $comment->updateStatusForUser($user);

        return $this->apiResponse($comment);
    }

    #[Route(path: '/comments/{id}/upvote', methods: ['DELETE'])]
    #[IsGranted(Permission::COMMENT_ADD)]
    public function downvote(Comment $comment, #[CurrentUser] User $user): JsonResponse
    {
        try {
            $command = new DownvoteCommentCommand($comment, $user);
            $this->sendCommand($command);

            $this->managerRegistry->getManager()->refresh($comment);
            $comment->updateStatusForUser($user);

            return $this->apiResponse($comment);
        } catch (\Exception) {
            return $this->apiResponse('Item not found', 404);
        }
    }

    #[Route(path: '/salt/case/export_comment/{itemType}/{itemId}/comment.csv', name: 'export_comment_file')]
    #[IsGranted(Permission::COMMENT_VIEW)]
    public function exportComment(string $itemType, string $itemId): Response
    {
        $response = new StreamedResponse();
        $response->setCallback(function () use ($itemType, $itemId): void {
            $childIds = [];
            $handle = fopen('php://output', 'wb+');
            if (false === $handle) {
                throw new \Exception('Unable to open output.');
            }
            $em = $this->managerRegistry->getManager();
            $repo = $em->getRepository(Comment::class);
            $lsItemRepo = $em->getRepository(LsItem::class);
            $lsDocRepo = $em->getRepository(LsDoc::class);
            $headers = ['Framework Name', 'Node Address', 'HumanCodingScheme', 'User', 'Organization', 'Comment', 'Attachment Url', 'Created Date', 'Updated Date'];
            fputcsv($handle, $headers, escape: '\\');

            switch ($itemType) {
                case 'document':
                    $lsDoc = $lsDocRepo->find($itemId) ?? $lsDocRepo->findOneBy(['identifier' => $itemId]);
                    if (null === $lsDoc) {
                        fclose($handle);
                        return;
                    }
                    $docId = $lsDoc->getId();
                    $commentData = $repo->findBy(['document' => $docId]);
                    $commentRows = $this->csvArray($commentData, $itemType);
                    foreach ($commentRows as $row) {
                        fputcsv($handle, $row, escape: '\\');
                    }
                    $lsDocChilds = $lsDoc->getLsItems();
                    foreach ($lsDocChilds as $lsDocChild) {
                        $childIds[] = $lsDocChild->getId();
                    }
                    break;

                case 'item':
                    /** @var ?LsItem $lsItem */
                    $lsItem = $lsItemRepo->find($itemId) ?? $lsItemRepo->findOneBy(['identifier' => $itemId]);

                    if (null !== $lsItem) {
                        $childIds = $lsItem->getDescendantIds();
                        $childIds[] = $lsItem->getId();
                    }
                    break;
            }

            if (count($childIds) > 0) {
                $commentData = $repo->findBy(['item' => $childIds]);
                $commentRows = $this->csvArray($commentData, 'item');
                foreach ($commentRows as $child_row) {
                    fputcsv($handle, $child_row, escape: '\\');
                }
            }

            fclose($handle);
        });

        $response->headers->set('content-type', 'text/csv; charset=utf-8;');
        $response->headers->set('Content-Disposition', 'attachment; filename = comment.csv');

        return $response;
    }

    /**
     * Get the export report data.
     *
     * @param array|Comment[] $commentData
     */
    private function csvArray(array $commentData, string $itemType): array
    {
        $comments = [];
        foreach ($commentData as $comment) {
            $comments[] = [
                ('item' === $itemType) ? $comment->getItem()->getLsDoc()->getTitle() : $comment->getDocument()->getTitle(),
                $this->url($itemType, $comment),
                ('item' === $itemType) ? $comment->getItem()->getHumanCodingScheme() : null,
                $comment->getUser()->getUserIdentifier(),
                $comment->getUser()->getOrg()->getName(),
                $comment->getContent(),
                $comment->getFileUrl(),
                $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                $comment->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $comments;
    }

    private function url(string $itemType, Comment $comment): ?string
    {
        if ('item' === $itemType) {
            return $this->generateUrl('doc_tree_item_view', ['id' => $comment->getItem()->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        if ('document' === $itemType) {
            return $this->generateUrl('doc_tree_view', ['slug' => $comment->getDocument()->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return null;
    }

    private function addComment(Request $request, string $itemType, LsItem|LsDoc $item, ?User $user, BucketService $bucket): JsonResponse
    {
        if (!$user instanceof User) {
            return new JsonResponse(['error' => ['message' => 'Invalid user']], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $parentId = $request->getPayload()->getInt('parent');
        } catch (\Throwable) {
            $parentId = null;
        }
        $content = $request->getPayload()->getString('content');
        $fileUrl = null;
        $fileMimeType = null;

        if ($this->featureManager->isEnabled('comment_attachments')) {
            $file = $request->files->get('file');

            if (!is_null($file) && $file->isValid()) {
                $fileUrl = $bucket->uploadFile($file, 'comments');
                $fileMimeType = $file->getMimeType();
            }
        }

        $command = new AddCommentCommand($itemType, $item, $user, $content, $fileUrl, $fileMimeType, $parentId);
        $this->sendCommand($command);

        $comment = $command->getComment();
        $comment->updateStatusForUser($user);

        return $this->apiResponse($comment);
    }

    private function serialize(mixed $data): string
    {
        return $this->serializer->serialize($data, 'json');
    }

    private function apiResponse(mixed $data, int $statusCode = 200): JsonResponse
    {
        $json = $this->serialize($data);

        return JsonResponse::fromJsonString($json, $statusCode);
    }
}
