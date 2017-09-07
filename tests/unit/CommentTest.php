<?php

use Salt\SiteBundle\Entity\Comment;
use Salt\SiteBundle\Entity\CommentUpvote;
use Salt\UserBundle\Entity\User;

class CommentTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testAddComment()
    {
        $comment = new Comment();

        $comment->setItem('document:123');
        $comment->setContent('unit test comment');
        $comment->setParent(null);
        $comment->setFullname('codeception');

        $em = $this->getModule('Doctrine2')->em;
        $em->persist($comment);
        $em->flush();

        $this->tester->seeInRepository(Comment::class, ['item' => 'document:123']);
    }

    public function testUpdateComment()
    {
        $commentId = $this->createComment();

        $em = $this->getModule('Doctrine2')->em;

        $comment = $em->find(Comment::class, $commentId);
        $comment->setContent('updated content');

        $em->persist($comment);
        $em->flush();

        $this->assertEquals('updated content', $comment->getContent());
        $this->tester->seeInRepository(Comment::class, ['content' => 'updated content']);
    }

    public function testDeleteComment()
    {
        $commentId = $this->createComment();

        $em = $this->getModule('Doctrine2')->em;
        $commentsCount = count($this->tester->grabEntitiesFromRepository(Comment::class));
        $comment = $em->find(Comment::class, $commentId);

        $em->remove($comment);
        $em->flush();
        $newCommentsCount = count($this->tester->grabEntitiesFromRepository(Comment::class));

        $this->assertEquals($commentsCount - 1, $newCommentsCount);
    }

    /* public function testUpvoteComment() */
    /* { */
    /*     $commentId = $this->createComment(); */
    /*     $em = $this->getModule('Doctrine2')->em; */

    /*     $comment = $em->find(Comment::class, $commentId); */

    /*     $this->tester->haveFakeRepository(Comment::class, array('addUpvoteForUser' => $this->addUpvoteForUser())); */
    /*     $comment->addUpvoteForUser($comment, $user); */
    /* } */

    public function createComment()
    {
        $commentId = $this->tester->haveInRepository(Comment::class,
            [
                'item' => 'document:1111',
                'content' => 'content',
                'parent' => null,
                'fullname' => 'codeception'
            ]
        );

        return $commentId;
    }

    public function addUpvoteForUser(Comment $comment, User $user, $em)
    {
        $commentUpvote = new CommentUpvote();
        $commentUpvote->setComment($comment);
        $commentUpvote->setUser($user);

        $em->persist($commentUpvote);
        $em->flush($commentUpvote);

        return $commentUpvote;
    }
}
