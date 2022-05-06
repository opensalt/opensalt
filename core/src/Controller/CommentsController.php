<?php

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
use App\Service\BucketService;
use Doctrine\Persistence\ManagerRegistry;
use Qandidate\Bundle\ToggleBundle\Annotations\Toggle;
use Qandidate\Toggle\Context;
use Qandidate\Toggle\ContextFactory;
use Qandidate\Toggle\ToggleManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Toggle("comments")
 */
class CommentsController extends AbstractController
{
    use CommandDispatcherTrait;

    private Context $context;

    public function __construct(
        private ToggleManager $manager,
        private SerializerInterface $serializer,
        private ManagerRegistry $managerRegistry,
        ContextFactory $contextFactory,
    ) {
        $this->context = $contextFactory->createContext();
    }

    /**
     * @Security("is_granted('comment')")
     */
    #[Route(path: '/comments/document/{id<\d+>}', name: 'create_doc_comment', methods: ['POST'])]
    public function newDocCommentAction(Request $request, LsDoc $doc, UserInterface $user, BucketService $bucket): JsonResponse
    {
        return $this->addComment($request, 'document', $doc, $user, $bucket);
    }

    /**
     * @Security("is_granted('comment')")
     */
    #[Route(path: '/comments/item/{id<\d+>}', name: 'create_item_comment', methods: ['POST'])]
    public function newItemCommentAction(Request $request, LsItem $item, UserInterface $user, BucketService $bucket): JsonResponse
    {
        return $this->addComment($request, 'item', $item, $user, $bucket);
    }

    /**
     * @Entity("comments", class="App\Entity\Comment\Comment", expr="repository.findByTypeItem(itemType, itemId)")
     * @Security("is_granted('comment_view')")
     *
     * @param array|Comment[] $comments
     *
     * @return mixed
     */
    #[Route(path: '/comments/{itemType<document|item>}/{itemId<\d+>}', name: 'get_comments', methods: ['GET'])]
    public function listAction(array $comments, UserInterface $user = null)
    {
        if ($user instanceof User) {
            foreach ($comments as $comment) {
                $comment->updateStatusForUser($user);
            }
        }

        return $this->apiResponse($comments);
    }

    /**
     * @Security("is_granted('comment_update', comment)")
     */
    #[Route(path: '/comments/{id}', methods: ['PUT'])]
    public function updateAction(Request $request, Comment $comment, UserInterface $user): JsonResponse
    {
        $command = new UpdateCommentCommand($comment, $request->request->get('content'));
        $this->sendCommand($command);

        return $this->apiResponse($comment);
    }

    /**
     * @Security("is_granted('comment_delete', comment)")
     */
    #[Route(path: '/comments/delete/{id}', methods: ['DELETE'])]
    public function deleteAction(Comment $comment, UserInterface $user): JsonResponse
    {
        $command = new DeleteCommentCommand($comment);
        $this->sendCommand($command);

        return $this->apiResponse('Ok', 200);
    }

    /**
     * @Security("is_granted('comment')")
     */
    #[Route(path: '/comments/{id}/upvote', methods: ['POST'])]
    public function upvoteAction(Comment $comment, UserInterface $user = null): JsonResponse
    {
        if (!$user instanceof User) {
            return new JsonResponse(['error' => ['message' => 'Invalid user']], Response::HTTP_UNAUTHORIZED);
        }

        $command = new UpvoteCommentCommand($comment, $user);
        $this->sendCommand($command);

        return $this->apiResponse($comment);
    }

    /**
     * @Security("is_granted('comment')")
     */
    #[Route(path: '/comments/{id}/upvote', methods: ['DELETE'])]
    public function downvoteAction(Comment $comment, UserInterface $user): JsonResponse
    {
        if (!$user instanceof User) {
            return new JsonResponse(['error' => ['message' => 'Invalid user']], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $command = new DownvoteCommentCommand($comment, $user);
            $this->sendCommand($command);

            return $this->apiResponse($comment);
        } catch (\Exception $e) {
            return $this->apiResponse('Item not found', 404);
        }
    }

    /**
     * @Security("is_granted('comment_view')")
     */
    #[Route(path: '/salt/case/export_comment/{itemType}/{itemId}/comment.csv', name: 'export_comment_file')]
    public function exportCommentAction(string $itemType, int $itemId): Response
    {
        $response = new StreamedResponse();
        $response->setCallback(function () use ($itemType, $itemId) {
            $childIds = [];
            $handle = fopen('php://output', 'wb+');
            $repo = $this->managerRegistry->getManager()->getRepository(Comment::class);
            $lsItemRepo = $this->managerRegistry->getManager()->getRepository(LsItem::class);
            $headers = ['Framework Name', 'Node Address', 'HumanCodingScheme', 'User', 'Organization', 'Comment', 'Attachment Url', 'Created Date', 'Updated Date'];
            fputcsv($handle, $headers);

            switch ($itemType) {
                case 'document':
                    $commentData = $repo->findBy([$itemType => $itemId]);
                    $commentRows = $this->csvArray($commentData, $itemType);
                    foreach ($commentRows as $row) {
                        fputcsv($handle, $row);
                    }
                    $lsDoc = $this->managerRegistry->getManager()->getRepository(LsDoc::class)->find($itemId);
                    $lsDocChilds = $lsDoc->getLsItems();
                    foreach ($lsDocChilds as $lsDocChild) {
                        $childIds[] = $lsDocChild->getId();
                    }
                    break;

                case 'item':
                    $lsItem = $lsItemRepo->find($itemId);

                    if (!is_null($lsItem)) {
                        $childIds = $lsItem->getDescendantIds();
                        $childIds[] = $itemId;
                    }
                    break;
            }

            $commentData = $repo->findBy(['item' => $childIds]);
            $commentRows = $this->csvArray($commentData, 'item');
            foreach ($commentRows as $child_row) {
                fputcsv($handle, $child_row);
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

    /**
     * Add a comment.
     *
     * @param LsItem|LsDoc $item
     */
    private function addComment(Request $request, string $itemType, $item, UserInterface $user, $bucket): JsonResponse
    {
        if (!$user instanceof User) {
            return new JsonResponse(['error' => ['message' => 'Invalid user']], Response::HTTP_UNAUTHORIZED);
        }

        $parentId = $request->request->get('parent');
        $content = $request->request->get('content');
        $fileUrl = null;
        $fileMimeType = null;

        if ($this->manager->active('comment_attachments', $this->context)) {
            $file = $request->files->get('file');

            if (!is_null($file) && $file->isValid()) {
                $fileUrl = $bucket->uploadFile($file, 'comments');
                $fileMimeType = $file->getMimeType();
            }
        }

        $command = new AddCommentCommand($itemType, $item, $user, $content, $fileUrl, $fileMimeType, (int) $parentId);
        $this->sendCommand($command);

        $comment = $command->getComment();

        return $this->apiResponse($comment);
    }

    private function serialize($data): string
    {
        return $this->serializer->serialize($data, 'json');
    }

    private function apiResponse($data, int $statusCode = 200): JsonResponse
    {
        $json = $this->serialize($data);

        return JsonResponse::fromJsonString($json, $statusCode);
    }
}
