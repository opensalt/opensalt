<?php

namespace Salt\SiteBundle\Controller;

use Salt\UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Salt\SiteBundle\Entity\Comment;
use Salt\SiteBundle\Entity\CommentUpvote;
use Qandidate\Bundle\ToggleBundle\Annotations\Toggle;

/**
 * @Toggle("comments")
 */
class CommentsController extends Controller
{
    /**
     * @Route("/comments", name="create_comment")
     *
     * @Method("POST")
     *
     * @Security("is_granted('comment')")
     */
    public function newAction(Request $request, UserInterface $user)
    {
        $comment = new Comment();
        $em = $this->getDoctrine()->getManager();

        $itemId = $request->request->get('itemId');
        $itemType = $request->request->get('itemType');
        $parentId = $request->request->get('parent');

        if ($this->existItem($itemId, $itemType)) {
            $comment->setContent(trim($request->request->get('content')));
            $comment->setUser($user);
            $comment->setFullname($user->getUsername().' - '.$user->getOrg()->getName());
            $comment->setItem($itemType.':'.$itemId);
            $comment->setCreatedByCurrentUser(true);

            if (!empty($parentId) && filter_var($parentId, FILTER_VALIDATE_INT)) {
                $parent = $em->getRepository('SaltSiteBundle:Comment')->find($parentId);
                $comment->setParent($parent);
            } else {
                $comment->setParent(null);
            }

            $em->persist($comment);
            $em->flush();

            return $this->apiResponse($comment);
        }

        return $this->apiResponse('Item not found', 404);
    }

    /**
     * @Route("/comments/{itemType}/{itemId}", name="get_comments")
     * @Method("GET")
     * @ParamConverter("comments", class="SaltSiteBundle:Comment", options={"id": {"itemType", "itemId"}, "repository_method" = "findByTypeItem"})
     * @Security("is_granted('comment_view')")
     *
     * @param array|Comment[] $comments
     * @param UserInterface|null $user
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
        $em = $this->getDoctrine()->getManager();
        $comment->setContent($request->request->get('content'));
        $em->persist($comment);
        $em->flush();

        return $this->apiResponse($comment);
    }

    /**
     * @Route("/comments/delete/{id}")
     *
     * @Method("DELETE")
     *
     * @Security("is_granted('comment')")
     */
    public function deleteAction(Comment $comment, UserInterface $user)
    {
        if ($comment->getUser() == $user) {
            $em = $this->getDoctrine()->getManager();

            $em->remove($comment);
            $em->flush();

            return $this->apiResponse('Ok', 200);
        }

        return $this->apiResponse('Unauthorized', 401);
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
        $em = $this->getDoctrine()->getManager();

        $commentUpvote = new CommentUpvote();
        $commentUpvote->setComment($comment);
        $commentUpvote->setUser($user);

        $em->persist($commentUpvote);
        $em->flush();

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
        $em = $this->getDoctrine()->getManager();

        $commentUpvote = $em->getRepository('SaltSiteBundle:CommentUpvote')->findOneBy(
            array('user' => $user, 'comment' => $comment)
        );

        if ($commentUpvote) {
            $em->remove($commentUpvote);
            $em->flush();

            return $this->apiResponse($comment);
        }

        return $this->apiResponse('Item not found', 404);
    }

    private function existItem($itemId, $itemType)
    {
        if (filter_var($itemId, FILTER_VALIDATE_INT)) {
            $em = $this->getDoctrine()->getManager();

            switch ($itemType) {
                case 'document':
                    $item = $em->getRepository('CftfBundle:LsDoc')->find($itemId);
                    break;
                case 'item':
                    $item = $em->getRepository('CftfBundle:LsItem')->find($itemId);
                    break;
                default:
                    return false;
            }

            if ($item) {
                return true;
            }
        }

        return false;
    }

    private function serialize($data)
    {
        return $this->get('jms_serializer')
            ->serialize($data, 'json');
    }

    private function apiResponse($data, $statusCode = 200)
    {
        $json = $this->serialize($data);

        return JsonResponse::fromJsonString($json, $statusCode);
    }
}
