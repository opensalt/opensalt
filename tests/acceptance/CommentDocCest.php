<?php

use Codeception\Exception\Skip;
use Codeception\Util\Locator;

class CommentDocCest
{
    static public $docPath = '/cftree/doc/';

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
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->seeElement('.jquery-comments');
        $I->see('To comment please login first');
    }

    public function dontSeeCommentsFormAsAnAnonymousUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->dontSeeElement('.jquery-comments .commenting-field');
        $I->see('To comment please login first');
    }

    public function seeCommentsSectionAsAnAuthenticatedUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->seeElement('.commenting-field');
    }

    public function commentAsAnAuthenticatedUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->createAComment('acceptance doc comment '.sq($I->getDocId()));
        $I->waitForJS('return $.active == 0;', 2);
        $I->see('acceptance doc comment '.sq($I->getDocId()), '.comment-wrapper .wrapper .content');

        // Verify a different user can see the comment
        $loginPage->logout();
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->waitForJS('return $.active == 0;', 2);
        $I->see('acceptance doc comment '.sq($I->getDocId()), '.comment-wrapper .wrapper .content');
    }

    public function upvoteOrDownvoteAsAnAnonymousUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->click(Locator::firstElement('.upvote'));
        $I->waitForJS('return $.active == 0;', 2);
        $I->seeCurrentUrlEquals('/login');
    }

    public function upvoteAsAnAuthenticatedUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
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
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->createAComment('downvote doc comment '.sq($I->getDocId()));
        $I->waitForJS('return $.active == 0', 2);
        $I->click(Locator::firstElement('.upvote'));
        $I->waitForJS('return $.active == 0', 2);
        $upvotes = $I->grabTextFrom(Locator::firstElement('.upvote'));
        $I->click(Locator::firstElement('.upvote'));
        $I->waitForJS('return $.active == 0', 2);
        $I->see($upvotes - 1, Locator::firstElement('.upvote'));
    }

    public function dontSeeCommentsInCopyItemsTab(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('Super User');
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->click('#rightSideCopyItemsBtn');
        $I->waitForElement('#tree2Section');
        $I->dontSeeElement('js-comments-container');
    }

    public function dontSeeCommentsInCreateAssociationsTab(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('Super User');
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->click('#rightSideCreateAssociationsBtn');
        $I->waitForElement('#tree2Section');
        $I->dontSeeElement('js-comments-container');
    }

    public function deleteComment(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->createAComment('acceptance doc comment '.sq($I->getDocId()));
        $I->waitForJS('return $.active == 0;', 2);
        $I->see('acceptance doc comment '.sq($I->getDocId()), '.comment-wrapper .wrapper .content');

        $I->click('.comment-wrapper .wrapper .actions .edit');
        $I->waitForJS('return $.active == 0;', 2);
        $I->click('.jquery-comments .commenting-field .textarea-wrapper .control-row .delete');
        $I->dontSee('acceptance doc comment '.sq($I->getDocId()), '.comment-wrapper .wrapper .content');
    }

    public function deleteUpvotedDownvotedComment(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->createAComment('acceptance doc comment '.sq($I->getDocId()));
        $I->waitForJS('return $.active == 0;', 2);
        $I->see('acceptance doc comment '.sq($I->getDocId()), '.comment-wrapper .wrapper .content');

        $loginPage->logout();
        $loginPage->loginAsRole('Admin');
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->waitForJS('return $.active == 0;', 2);
        $upvotes = $I->grabTextFrom(Locator::firstElement('.upvote'));

        $I->click(Locator::firstElement('.upvote'));
        $I->waitForJS('return $.active == 0', 2);
        $I->see($upvotes + 1, Locator::firstElement('.upvote'));

        $loginPage->logout();
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->waitForJS('return $.active == 0;', 2);

        $I->click('.comment-wrapper .wrapper .actions .edit');
        $I->waitForJS('return $.active == 0;', 2);
        $I->click('.jquery-comments .commenting-field .textarea-wrapper .control-row .delete');
        $I->dontSee('acceptance doc comment '.sq($I->getDocId()), '.comment-wrapper .wrapper .content');
    }

    public function deleteRepliedComment(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->createAComment('acceptance doc replied comment '.sq($I->getDocId()));
        $I->waitForJS('return $.active == 0;', 2);
        $I->see('acceptance doc replied comment '.sq($I->getDocId()), '.comment-wrapper .wrapper .content');

        $loginPage->logout();
        $loginPage->loginAsRole('Admin');
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->waitForJS('return $.active == 0;', 2);

        $I->click(Locator::firstElement('.reply'));
        $I->fillField('.jquery-comments .data-container .main .comment .child-comments .commenting-field .textarea-wrapper .textarea', 'reply');
        $I->click('.jquery-comments .data-container .main .comment .child-comments .commenting-field .textarea-wrapper .control-row .send');
        $I->waitForJS('return $.active == 0;', 2);

        $loginPage->logout();
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->waitForJS('return $.active == 0;', 2);

        $I->click('.comment-wrapper .wrapper .actions .edit');
        $I->waitForJS('return $.active == 0;', 2);
        $I->click('.jquery-comments .commenting-field .textarea-wrapper .control-row .delete');
        $I->dontSee('acceptance doc replied comment '.sq($I->getDocId()), '.comment-wrapper .wrapper .content');
    }
}
