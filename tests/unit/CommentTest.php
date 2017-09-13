<?php

use Salt\SiteBundle\Entity\Comment;
use Salt\SiteBundle\Entity\CommentUpvote;
use Salt\UserBundle\Entity\Organization;
use Salt\UserBundle\Entity\User;

class CommentTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    // tests
    public function testAddComment()
    {
        $this->tester->ensureUserExistsWithRole('Editor');
        $user = $this->tester->getLastUser();
        $comment = new Comment();

        $comment->setItem('document:123');
        $comment->setContent('unit test comment');
        $comment->setParent(null);
        $comment->setFullname('codeception');
        $comment->setUser($user);

        $em = $this->getModule('Doctrine2')->em;
        $em->persist($comment);
        $em->flush();

        $this->tester->seeInRepository(Comment::class, ['item' => 'document:123']);
    }

    public function testUpdateComment()
    {
        $commentId = $this->createComment('new comment');

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
        $commentId = $this->createComment('deleted comment');

        $em = $this->getModule('Doctrine2')->em;
        $commentsCount = count($this->tester->grabEntitiesFromRepository(Comment::class));
        $comment = $em->find(Comment::class, $commentId);

        $em->remove($comment);
        $em->flush();
        $newCommentsCount = count($this->tester->grabEntitiesFromRepository(Comment::class));

        $this->assertEquals($commentsCount - 1, $newCommentsCount);
    }

    public function testUpvoteComment()
    {
        $em = $this->getModule('Doctrine2')->em;
        $commentId = $this->createComment('upvoted comment');
        $comment = $em->find(Comment::class, $commentId);

        $upvotes = $comment->getUpvoteCount();
        $user = $this->tester->getLastUser();

        $commentUpvote = new CommentUpvote();
        $commentUpvote->setComment($comment);
        $commentUpvote->setUser($user);

        $em->persist($commentUpvote);
        $em->flush();

        $upvotesCount = $comment->getUpvoteCount();

        $this->assertEquals($upvotes + 1, $upvotesCount);
    }

    public function testDownvoteComment()
    {
        $comment = $this->createComment('upvoted comment');
        $upvotes = $comment->getUpvoteCount();
        $user = $this->tester->getLastUser();

        $comment->addUpvoteForUser($comment, $user);
        $upvotes = $comment->getUpvoteCount();

        $comment->removeUpvoteForUser($comment, $user);
        $upvotesCount = $comment->getUpvoteCount();

        $this->assertEquals($upvotes - 1, $upvotesCount);
    }

    private function createComment($content)
    {
        $this->tester->ensureUserExistsWithRole('Editor');
        $user = $this->tester->getLastUser();

        $commentId = $this->tester->haveInRepository(Comment::class,
            [
                'item' => 'document:1111',
                'content' => $content,
                'parent' => null,
                'fullname' => 'codeception',
                'user' => $user
            ]
        );

        return $commentId;
    }
}
