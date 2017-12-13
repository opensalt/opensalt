<?php

use Doctrine\ORM\EntityManager;
use Salt\SiteBundle\Entity\Comment;
use Salt\SiteBundle\Entity\CommentUpvote;

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
        /** @var EntityManager $em */
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

        $em->detach($comment);
        $comment = $em->find(Comment::class, $commentId);
        $upvotesCount = $comment->getUpvoteCount();
        $this->assertEquals($upvotes + 1, $upvotesCount);
    }

    public function testDownvoteComment()
    {
        /** @var EntityManager $em */
        $em = $this->getModule('Doctrine2')->em;
        $commentRepo = $em->getRepository(Comment::class);
        $commentId = $this->createComment('upvoted comment');
        $comment = $commentRepo->find($commentId);

        $upvotes = $comment->getUpvoteCount();
        $user = $this->tester->getLastUser();

        // Add an upvote
        $commentRepo->addUpvoteForUser($comment, $user);
        $em->flush();
        $em->detach($comment);
        $comment = $commentRepo->find($commentId);
        $upvotesCount = $comment->getUpvoteCount();
        $this->assertEquals($upvotes + 1, $upvotesCount);

        // Remove the upvote
        $commentRepo->removeUpvoteForUser($comment, $user);
        $em->flush();
        $em->detach($comment);
        $comment = $commentRepo->find($commentId);
        $upvotesCount = $comment->getUpvoteCount();
        $this->assertEquals($upvotes, $upvotesCount);
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

    public function testAddCommentMoreThan255Chars()
    {
        $this->tester->ensureUserExistsWithRole('Editor');
        $user = $this->tester->getLastUser();
        $comment = new Comment();

        $comment->setItem('document:1234');
        $comment->setContent("Lorem Ipsum is simply dummy text of the printing and
            typesetting industry. Lorem Ipsum has been the industry's standard dummy
            text ever since the 1500s, when an unknown printer took a galley of type
            and scrambled it to make a type specimen book. It has survived not only
            five centuries, but also the leap into electronic typesetting,
            remaining essentially unchanged. It was popularised in the 1960s with the
            release of Letraset sheets containing Lorem Ipsum passages, and more recently
            with desktop publishing software like Aldus PageMaker including versions of
            Lorem Ipsum.");
        $comment->setParent(null);
        $comment->setUser($user);

        $em = $this->getModule('Doctrine2')->em;
        $em->persist($comment);
        $em->flush();

        $this->tester->seeInRepository(Comment::class, ['item' => 'document:1234']);
    }
}
