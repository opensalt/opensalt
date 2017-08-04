<?php

namespace Salt\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Salt\SiteBundle\Entity\Comment;
use Salt\SiteBundle\Entity\CommentUpvote;

class CommentsController extends Controller
{
    /**
     * @Route("/comments", name="create_comment")
     * @Method("POST")
     */
    public function newAction(Request $request)
    {
        $comment = new Comment();
        $user = $this->getUser();

        $comment->setContent(trim($request->request->get('content')));
        $comment->setCommentId($request->request->get('id'));
        $comment->setParent(
            empty($request->request->get('parent'))?null:$request->request->get('parent')
        );
        $comment->setUserId($user->getId());
        $comment->setFullname($user->getUsername().' - '.$user->getOrg()->getName());
        $comment->setItem($request->request->get('itemType').':'.$request->request->get('itemId'));

        $em = $this->getDoctrine()->getManager();
        $em->persist($comment);
        $em->flush();

        $response = $this->apiResponse($comment);
        return $response;
    }

    /**
     * @Route("/comments/{itemId}/{itemType}", name="get_comments")
     * @Method("GET")
     */
    public function listAction($itemId, $itemType)
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();

        $comments = $em->getRepository('SaltSiteBundle:Comment')->findByItem($itemType.':'.$itemId);

        foreach ($comments as $comment){
            if ($user) {
                if ($comment->getUserId() == $user->getId()){
                    $comment->setCreatedByCurrentUser(true);
                    $comment->setUserHasUpvoted(($comment->getUpvoteCount() > 0)?true:false);
                }
            }
        }

        $response = $this->apiResponse($comments);
        return $response;
    }

    /**
     * @Route("/comments/{id}")
     * @Method("POST")
     */
    public function updateAction(Comment $comment, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $comment->setContent($request->request->get('content'));
        $em->persist($comment);
        $em->flush($comment);

        $response = $this->apiResponse($comment);
        return $response;
    }

    /**
     * @Route("/comments/delete/{id}")
     * @Method("DELETE")
     */
    public function deleteAction(Comment $comment)
    {
        if (!$comment) {
            return new Response(410);
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($comment);
        $em->flush();

        return new Response(200);
    }

    /**
     * @Route("/comments/{id}/upvote")
     * @Method("POST")
     */
    public function upvoteAction(Comment $comment)
    {
        if (!$comment) {
            return new Response(410);
        }

        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $comment->setUpvoteCount(
            $comment->getUpvoteCount() + 1
        );

        $commentUpvote = new CommentUpvote();
        $commentUpvote->setCommentId($comment->getId());
        $commentUpvote->setUserId($user->getId());

        $em->persist($comment);
        $em->persist($commentUpvote);

        $em->flush();

        $response = $this->apiResponse($comment);
        return $response;
    }

    /**
     * @Route("/comments/{id}/downvote")
     * @Method("POST")
     */
    public function downvoteAction(Comment $comment)
    {
        if (!$comment) {
            return new Response(410);
        }

        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $commentUpvote = $em->getRepository('SaltSiteBundle:CommentUpvote')->findOneBy(
            array('userId' => $user->getId(), 'commentId' => $comment->getId())
        );

        if ($commentUpvote) {
            $comment->setUpvoteCount(
                $comment->getUpvoteCount() - 1
            );

            $em->persist($comment);
            $em->remove($commentUpvote);

            $em->flush();


            $response = $this->apiResponse($comment);
            return $response;
        }

        return new Response(404);
    }

    private function serialize($data)
    {
        return $this->get('jms_serializer')
            ->serialize($data, 'json');
    }

    private function apiResponse($data, $statusCode = 200)
    {
        $json = $this->serialize($data);

        return new Response($json, $statusCode, [
            'Content-Type' => 'application/json'
        ]);
    }
}
