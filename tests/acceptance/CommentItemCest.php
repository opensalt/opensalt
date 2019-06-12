<?php

use Codeception\Scenario;
use Codeception\Util\Locator;
use Context\Login;

class CommentItemCest
{
    static public $itemPath = '/cftree/item/';

    public function _before(AcceptanceTester $I)
    {
        $I->assertFeatureEnabled('comments');
    }

    // tests
    public function seeCommentsSectionAsAnAnonymousUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->seeElement('.jquery-comments');
        $I->see('To comment please login first');
    }

    public function dontSeeCommentsFormAsAnAnonymousUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->dontSeeElement('.jquery-comments .commenting-field');
        $I->see('To comment please login first');
    }

    public function seeCommentsSectionAsAnAuthenticatedUser(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->waitForElementVisible('.commenting-field', 120);
        $I->seeElement('.commenting-field');
    }

    public function commentAsAnAuthenticatedUser(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->createAComment('acceptance item comment '.sq($I->getItemId()));
        $I->waitForJS('return $.active == 0;', 2);
        $I->see('acceptance item comment '.sq($I->getItemId()), '.comment-wrapper .wrapper .content');

        // Verify a different user can see the comment
        $loginPage->logout();
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->waitForJS('return $.active == 0;', 2);
        $I->see('acceptance item comment '.sq($I->getItemId()), '.comment-wrapper .wrapper .content');
    }

    public function upvoteOrDownvoteAsAnAnonymousUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->click(Locator::firstElement('.upvote'));
        $I->waitForJS('return $.active == 0;', 2);
        $I->seeCurrentUrlEquals('/login');
    }

    public function upvoteAsAnAuthenticatedUser(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $upvotes = $I->grabTextFrom(Locator::firstElement('.upvote'));
        $I->click(Locator::firstElement('.upvote'));
        $I->waitForJS('return $.active == 0', 2);
        $I->see($upvotes + 1, Locator::firstElement('.upvote'));
    }

    public function downvoteAsAnAuthenticatedUser(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->createAComment('downvote comment '.sq($I->getItemId()));
        $I->waitForJS('return $.active == 0', 2);
        $I->click(Locator::firstElement('.upvote'));
        $I->waitForJS('return $.active == 0', 2);
        $upvotes = $I->grabTextFrom(Locator::firstElement('.upvote'));
        $I->click(Locator::firstElement('.upvote'));
        $I->waitForJS('return $.active == 0', 2);
        $I->see($upvotes - 1, Locator::firstElement('.upvote'));
    }

    public function dontSeeCommentsInCopyItemTab(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Super User');
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->click('#rightSideCopyItemsBtn');
        $I->waitForElement('#tree2Section');
        $I->dontSeeElement('js-comments-container');
    }

    public function dontSeeCommentsInCreateAssociationsTab(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Super User');
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->click('#rightSideCreateAssociationsBtn');
        $I->waitForElement('#tree2Section');
        $I->dontSeeElement('js-comments-container');
    }

    public function deleteComment(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->createAComment('acceptance doc comment '.sq($I->getItemId()));
        $I->waitForJS('return $.active == 0;', 2);
        $I->see('acceptance doc comment '.sq($I->getItemId()), '.comment-wrapper .wrapper .content');

        $I->click('.comment-wrapper .wrapper .actions .edit');
        $I->waitForJS('return $.active == 0;', 2);
        $I->click('.jquery-comments .commenting-field .textarea-wrapper .control-row .delete');
        $I->dontSee('acceptance doc comment '.sq($I->getItemId()), '.comment-wrapper .wrapper .content');
    }

    public function deleteUpvotedDownvotedComment(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->createAComment('acceptance doc comment '.sq($I->getItemId()));
        $I->waitForJS('return $.active == 0;', 2);
        $I->see('acceptance doc comment '.sq($I->getItemId()), '.comment-wrapper .wrapper .content');

        $loginPage->logout();
        $loginPage->loginAsRole('Admin');
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->waitForJS('return $.active == 0;', 2);
        $upvotes = $I->grabTextFrom(Locator::firstElement('.upvote'));

        $I->click(Locator::firstElement('.upvote'));
        $I->waitForJS('return $.active == 0', 2);
        $I->see($upvotes + 1, Locator::firstElement('.upvote'));

        $loginPage->logout();
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->waitForJS('return $.active == 0;', 2);

        $I->click('.comment-wrapper .wrapper .actions .edit');
        $I->waitForJS('return $.active == 0;', 2);
        $I->click('.jquery-comments .commenting-field .textarea-wrapper .control-row .delete');
        $I->dontSee('acceptance doc comment '.sq($I->getItemId()), '.comment-wrapper .wrapper .content');
    }
}
