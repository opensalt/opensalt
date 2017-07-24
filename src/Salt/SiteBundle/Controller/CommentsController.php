<?php

namespace Salt\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Salt\SiteBundle\Entity\Comment;

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

        $comment->setContent($request->request->get('content'));
        $comment->setCommentId($request->request->get('id'));
        $comment->setParent(
            empty($request->request->get('parent'))?0:$request->request->get('parent')
        );
        $comment->setUserId($user->getId());
        $comment->setFullname($user->getUsername().' - '.$user->getOrg()->getName());
        $comment->setUpvoteCount($request->request->get('upvote_count'));
        $comment->setUserHasUpvoted(false);
        $comment->setItem($request->request->get('itemType').$request->request->get('itemId'));
        $comment->setCreatedAt(new \DateTime($request->request->get('created')));
        $comment->setUpdatedAt(new \DateTime($request->request->get('modified')));

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

        $comments = $em->getRepository('SaltSiteBundle:Comment')->findByItem($itemType.$itemId);

        foreach ($comments as $comment){
            if ($comment->getParent() == 0) {
                $comment->setParent(null);
            }
            if ($user) {
                if ($comment->getUserId() == $user->getId()){
                    $comment->setCreatedByCurrentUser(true);
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
     * @Method("POST")
     */
    public function deleteAction(Comment $comment)
    {
        if (!$comment) {
            throw $this->createNotFoundException('Unable to find the comment');
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($comment);
        $em->flush();

        return new Response(200);
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
