<?php


use Codeception\Exception\Skip;

class CommentCest
{
    public function _before(AcceptanceTester $I)
    {
        $toggles = $I->grabService('qandidate.toggle.manager');
        $context = $I->grabService('qandidate.toggle.context_factory');

        if (!$toggles->active('comments', $context->createContext())) {
            throw new Skip();
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
        $I->click('.textarea');
        $I->fillField('.textarea', 'acceptance comment');
        $I->click('.send');
        $I->wait(2);
        $I->see('acceptance comment', '.comment-wrapper .wrapper .content');
    }

    public function upvoteAsAnAnonymousUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $I->amOnPage('/cftree/doc/'.$I->getDocId());
        $I->click('.upvote');
        $I->wait(2);
        $I->seeCurrentUrlEquals('/login');
    }

    public function upvoteAsAnAuthenticatedUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage('/cftree/doc/'.$I->getDocId());
        $upvotes = $I->grabTextFrom('.upvote');
        $I->click('.upvote');
        $I->wait(2);
        $I->see($upvotes + 1, '.upvote');
    }
}
