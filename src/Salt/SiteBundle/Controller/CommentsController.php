<?php

namespace Salt\SiteBundle\Controller;

use App\Command\CommandDispatcher;
use App\Command\Comment\AddCommentCommand;
use App\Command\Comment\DeleteCommentCommand;
use App\Command\Comment\DownvoteCommentCommand;
use App\Command\Comment\UpdateCommentCommand;
use App\Command\Comment\UpvoteCommentCommand;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Salt\UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Salt\SiteBundle\Entity\Comment;
use Qandidate\Bundle\ToggleBundle\Annotations\Toggle;

/**
 * @Toggle("comments")
 */
class CommentsController extends Controller
{
    use CommandDispatcher;

    /**
     * @Route("/comments/document/{id}", name="create_doc_comment")
     *
     * @Method("POST")
     *
     * @Security("is_granted('comment')")
     */
    public function newDocCommentAction(Request $request, LsDoc $doc, UserInterface $user)
    {
        return $this->addComment($request, 'document', $doc->getId(), $user);
    }

    /**
     * @Route("/comments/item/{id}", name="create_item_comment")
     *
     * @Method("POST")
     *
     * @Security("is_granted('comment')")
     */
    public function newItemCommentAction(Request $request, LsItem $item, UserInterface $user)
    {
        return $this->addComment($request, 'item', $item->getId(), $user);
    }

    /**
     * @Route("/comments/{itemType}/{itemId}", name="get_comments")
     * @Method("GET")
     * @ParamConverter("comments", class="SaltSiteBundle:Comment", options={"id": {"itemType", "itemId"}, "repository_method" = "findByTypeItem"})
     * @Security("is_granted('comment_view')")
     *
     * @param array|Comment[] $comments
     * @param UserInterface|null $user
     *
     * @return mixed
     */
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
     * @Route("/comments/{id}")
     *
     * @Method("PUT")
     *
     * @Security("is_granted('comment_update', comment)")
     */
    public function updateAction(Comment $comment, Request $request, UserInterface $user)
    {
        $command = new UpdateCommentCommand($comment, $request->request->get('content'));
        $this->sendCommand($command);

        return $this->apiResponse($comment);
    }

    /**
     * @Route("/comments/delete/{id}")
     *
     * @Method("DELETE")
     *
     * @Security("is_granted('comment_delete', comment)")
     */
    public function deleteAction(Comment $comment, UserInterface $user)
    {
        $command = new DeleteCommentCommand($comment);
        $this->sendCommand($command);

        return $this->apiResponse('Ok', 200);
    }

    /**
     * @Route("/comments/{id}/upvote")
     *
     * @Method("POST")
     *
     * @Security("is_granted('comment')")
     */
    public function upvoteAction(Comment $comment, UserInterface $user)
    {
        if (!$user instanceof User) {
            return new JsonResponse(['error' => ['message' => 'Invalid user']], Response::HTTP_UNAUTHORIZED);
        }

        $command = new UpvoteCommentCommand($comment, $user);
        $this->sendCommand($command);

        return $this->apiResponse($comment);
    }

    /**
     * @Route("/comments/{id}/upvote")
     *
     * @Method("DELETE")
     *
     * @Security("is_granted('comment')")
     */
    public function downvoteAction(Comment $comment, UserInterface $user)
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
     * Add a comment
     *
     * @param Request $request
     * @param string $itemType
     * @param int $itemId
     * @param UserInterface $user
     *
     * @return JsonResponse
     */
    private function addComment(Request $request, string $itemType, $itemId, UserInterface $user): Response
    {
        if (!$user instanceof User) {
            return new JsonResponse(['error' => ['message' => 'Invalid user']], Response::HTTP_UNAUTHORIZED);
        }

        $parentId = $request->request->get('parent');
        $content = $request->request->get('content');

        $command = new AddCommentCommand($itemType, (int) $itemId, $user, $content, (int) $parentId);
        $this->sendCommand($command);

        $comment = $command->getComment();

        return $this->apiResponse($comment);
    }

    private function serialize($data)
    {
        return $this->get('jms_serializer')
            ->serialize($data, 'json');
    }

    private function apiResponse($data, $statusCode = 200): JsonResponse
    {
        $json = $this->serialize($data);

        return JsonResponse::fromJsonString($json, $statusCode);
    }
}
