<?php

use Codeception\Exception\Skip;
use Codeception\Util\Locator;

class CommentItemCest
{
    static public $itemPath = '/cftree/item/';

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
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->seeElement('.jquery-comments');
        $I->see('To comment please login first');
    }

    public function dontSeeCommentsFormAsAnAnonymousUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->dontSeeElement('.jquery-comments .commenting-field');
        $I->see('To comment please login first');
    }

    public function seeCommentsSectionAsAnAuthenticatedUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->seeElement('.commenting-field');
    }

    public function commentAsAnAuthenticatedUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->createAComment('acceptance item comment '.sq($I->getItemId()));
        $I->waitForJS('return $.active == 0;', 2);
        $I->see('acceptance item comment '.sq($I->getItemId()), '.comment-wrapper .wrapper .content');

        // Verify a different user can see the comment
        $loginPage->logout();
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->waitForJS('return $.active == 0;', 2);
        $I->see('acceptance item comment '.sq($I->getItemId()), '.comment-wrapper .wrapper .content');
    }

    public function upvoteOrDownvoteAsAnAnonymousUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->click(Locator::firstElement('.upvote'));
        $I->waitForJS('return $.active == 0;', 2);
        $I->seeCurrentUrlEquals('/login');
    }

    public function upvoteAsAnAuthenticatedUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$itemPath.$I->getItemId());
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
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->createAComment('downvote comment '.sq($I->getItemId()));
        $I->waitForJS('return $.active == 0', 2);
        $I->click(Locator::firstElement('.upvote'));
        $I->waitForJS('return $.active == 0', 2);
        $upvotes = $I->grabTextFrom(Locator::firstElement('.upvote'));
        $I->click(Locator::firstElement('.upvote'));
        $I->waitForJS('return $.active == 0', 2);
        $I->see($upvotes - 1, Locator::firstElement('.upvote'));
    }
}
