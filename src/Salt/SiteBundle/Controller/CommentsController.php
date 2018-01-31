<?php

namespace Salt\SiteBundle\Controller;

use App\Command\CommandDispatcherTrait;
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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Salt\SiteBundle\Entity\Comment;
use Qandidate\Bundle\ToggleBundle\Annotations\Toggle;

/**
 * @Toggle("comments")
 */
class CommentsController extends Controller
{
    use CommandDispatcherTrait;

    /**
     * @Route("/comments/document/{id}", name="create_doc_comment")
     *
     * @Method("POST")
     *
     * @Security("is_granted('comment')")
     */
    public function newDocCommentAction(Request $request, LsDoc $doc, UserInterface $user)
    {
        return $this->addComment($request, 'document', $doc, $user);
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
        return $this->addComment($request, 'item', $item, $user);
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
        if ($user instanceof User)
        {
            foreach ($comments as $comment)
            {
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
     * @Route("/salt/case/export_comment/{itemType}/{itemId}/comment.csv", name="export_comment_file")
     *
     * @param int $itemId
     * @param string $itemType
     * @param Request $request
     *
     * @return Response
     */
    public function exportCommentAction(string $itemType, int $itemId)
    {
        $url = ($itemType === 'item') ? $this->generateUrl('doc_tree_item_view', ['id' => $itemId], UrlGeneratorInterface::ABSOLUTE_URL) : $this->generateUrl('doc_tree_view', ['slug' => $itemId], UrlGeneratorInterface::ABSOLUTE_URL);
        $repo = $this->getDoctrine()->getManager()->getRepository(Comment::class);
        $headers = ['Framework Name', 'Node Address', ($itemType === 'item') ? 'HumanCodingScheme' : null, 'User', 'Organization', 'Comment', 'Created Date', 'Updated Date'];
        $rows[] = implode(',', array_filter($headers));
        $comment_data = $repo->findBy([$itemType => $itemId]);

        foreach ($comment_data as $comment) {
            $comments=[
                ($itemType === 'item') ? '"'.$comment->getItem()->getLsDoc()->getTitle().'"' : '"'.$comment->getDocument()->getTitle().'"',
                $url,
                ($itemType === 'item') ? '"'.$comment->getItem()->getHumanCodingScheme().'"' : null,
                '"'.$comment->getUser()->getUsername().'"',
                '"'.$comment->getUser()->getOrg()->getName().'"',
                '"'.$comment->getContent().'"',
                $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                $comment->getUpdatedAt()->format('Y-m-d H:i:s')
            ];
            $rows[] = implode(',', array_filter($comments));
        }

        $content = implode("\n", $rows);
        $response = new Response($content);
        $response->headers->set('content_type', 'text/csv');
        return $response;
    }

    /**
     * Add a comment
     *
     * @param Request $request
     * @param string $itemType
     * @param $item
     * @param UserInterface $user
     *
     * @return JsonResponse
     */
    private function addComment(Request $request, string $itemType, $item, UserInterface $user): Response
    {
        if (!$user instanceof User) {
            return new JsonResponse(['error' => ['message' => 'Invalid user']], Response::HTTP_UNAUTHORIZED);
        }

        $parentId = $request->request->get('parent');
        $content = $request->request->get('content');

        $command = new AddCommentCommand($itemType, $item, $user, $content, (int) $parentId);
        $this->sendCommand($command);

        $comment = $command->getComment();

        return $this->apiResponse($comment);
    }

    private function serialize($data)
    {
        return $this->get('jms_serializer')
            ->serialize($data, 'json');
    }

    private function apiResponse($data, $statusCode=200): JsonResponse
    {
        $json = $this->serialize($data);

        return JsonResponse::fromJsonString($json, $statusCode);
    }
}
