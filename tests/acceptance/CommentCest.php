<?php

use Codeception\Exception\Skip;
use Codeception\Util\Locator;

class CommentCest
{
    public function _before(AcceptanceTester $I)
    {
        $toggles = $I->grabService('qandidate.toggle.manager');
        $context = $I->grabService('qandidate.toggle.context_factory');

        if (!$toggles->active('comments', $context->createContext())) {
            throw new Skip();
        } else {
            $I->getLastFrameworkId();
            $loginPage = new \Page\Login($I);
            $loginPage->loginAsRole('Editor');
            $I->amOnPage('/cftree/doc/'.$I->getDocId());
            $I->createAComment('default test comment');
            $loginPage->logout();
        }
    }

    // tests
    public function seeCommentsSectionAsAnAnonymousUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $I->amOnPage('/cftree/doc/'.$I->getDocId());
        $I->seeElement('.jquery-comments');
        $I->see('To comment please login first');
    }

    public function seeCommentsSectionAsAnAuthenticatedUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage('/cftree/doc/'.$I->getDocId());
        $I->seeElement('.commenting-field');
    }

    public function commentAsAnAuthenticatedUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage('/cftree/doc/'.$I->getDocId());
        $I->createAComment('acceptance comment');
        $I->see('acceptance comment', '.comment-wrapper .wrapper .content');
    }

    public function upvoteOrDownvoteAsAnAnonymousUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $I->amOnPage('/cftree/doc/'.$I->getDocId());
        $I->click(Locator::firstElement('.upvote'));
        $I->waitForJS('return $.active == 0;', 2);
        $I->seeCurrentUrlEquals('/login');
    }

    public function upvoteAsAnAuthenticatedUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage('/cftree/doc/'.$I->getDocId());
        $upvotes = $I->grabTextFrom(Locator::firstElement('.upvote'));
        $I->click(Locator::firstElement('.upvote'));
        $I->waitForJS('return $.active == 0', 2);
        $I->see($upvotes + 1, Locator::firstElement('.upvote'));
    }

    public function downvoteAsAnAuthenticatedUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage('/cftree/doc/'.$I->getDocId());
        $I->createAComment('downvote comment');
        $I->waitForJS('return $.active == 0', 2);
        $I->click(Locator::firstElement('.upvote'));
        $I->waitForJS('return $.active == 0', 2);
        $upvotes = $I->grabTextFrom(Locator::firstElement('.upvote'));
        $I->click(Locator::firstElement('.upvote'));
        $I->waitForJS('return $.active == 0', 2);
        $I->see($upvotes - 1, Locator::firstElement('.upvote'));
    }
}
