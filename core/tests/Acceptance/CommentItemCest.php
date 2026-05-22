<?php

namespace Tests\Acceptance;

use Codeception\Scenario;
use Tests\Support\AcceptanceTester;
use Tests\Support\Context\Login;

class CommentItemCest
{
    public static $itemPath = '/editor/';

    public function _before(AcceptanceTester $I)
    {
        $I->assertFeatureEnabled('comments');
    }

    // tests
    public function seeCommentsSectionAsAnAnonymousUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->waitForElementVisible('.comment-module', 30);
        $I->seeElement('.comment-module');
        $I->waitForElementVisible('.login-prompt', 10);
        $I->see('Log in to post comments.', '.login-prompt');
    }

    public function dontSeeCommentsFormAsAnAnonymousUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->waitForElementVisible('.comment-module', 30);
        // Textarea is present in the DOM but disabled for anonymous users
        $I->seeElement('.comment-textarea[disabled]');
        $I->dontSeeElement('.comment-textarea:not([disabled])');
        $I->waitForElementVisible('.login-prompt', 10);
        $I->see('Log in to post comments.', '.login-prompt');
    }

    public function seeCommentsSectionAsAnAuthenticatedUser(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->waitForElementVisible('.comment-textarea', 30);
        $I->seeElement('.comment-textarea');
    }

    public function commentAsAnAuthenticatedUser(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->createAComment('acceptance item comment ' . sq($I->getItemId()));
        $I->waitForText('acceptance item comment ' . sq($I->getItemId()), 10, '.comment-body');
        $I->see('acceptance item comment ' . sq($I->getItemId()), '.comment-body');

        // Verify a different user can see the comment
        $loginPage->logout();
        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->waitForText('acceptance item comment ' . sq($I->getItemId()), 10, '.comment-body');
        $I->see('acceptance item comment ' . sq($I->getItemId()), '.comment-body');
    }

    public function upvoteOrDownvoteAsAnAnonymousUser(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();

        // Create a comment first to ensure there's something to upvote
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->createAComment('item comment to upvote anonymous');
        $loginPage->logout();

        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->waitForElementVisible('.comment-module', 120);
        $I->waitForElementVisible(['css' => '.comment-item:first-of-type .upvote-btn[disabled]'], 60);
    }

    public function upvoteAsAnAuthenticatedUser(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementNotVisible('.spinner-border', 120);

        // Ensure there is a comment
        $I->createAComment('item comment to upvote auth');

        $I->waitForElementVisible(['css' => '.comment-item:first-of-type .upvote-btn'], 60);
        $upvotesText = $I->grabTextFrom(['css' => '.comment-item:first-of-type .upvote-btn']);
        $upvotes = (int) trim($upvotesText);

        $I->click(['css' => '.comment-item:first-of-type .upvote-btn[title="Upvote"]']);
        $I->waitForText((string)($upvotes + 1), 60, ['css' => '.comment-item:first-of-type .upvote-btn']);
        $I->see((string)($upvotes + 1), ['css' => '.comment-item:first-of-type .upvote-btn']);
    }

    public function downvoteAsAnAuthenticatedUser(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->createAComment('downvote comment ' . sq($I->getItemId()));
        $I->waitForElementVisible(['css' => '.comment-item:first-of-type .upvote-btn'], 60);
        $I->click(['css' => '.comment-item:first-of-type .upvote-btn[title="Upvote"]']);
        $I->waitForElementVisible(['css' => '.comment-item:first-of-type .upvote-btn[title="Remove upvote"]']);

        $I->waitForElementVisible(['css' => '.comment-item:first-of-type .upvote-btn .upvote-count'], 60);
        $upvotesText = $I->grabTextFrom(['css' => '.comment-item:first-of-type .upvote-btn']);
        $upvotes = (int) trim($upvotesText);

        $I->click(['css' => '.comment-item:first-of-type .upvote-btn[title="Remove upvote"]']);
        $I->waitForElementVisible(['css' => '.comment-item:first-of-type .upvote-btn[title="Upvote"]']);
        if ($upvotes > 2) {
            $I->waitForText((string)($upvotes - 1), 60, ['css' => '.comment-item:first-of-type .upvote-btn']);
        } else {
            $I->waitForElementNotVisible(['css' => '.comment-item:first-of-type .upvote-btn .upvote-count'], 60);
        }
    }

    public function dontSeeCommentsInCopyItemTab(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Super User');
        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        // Use XPath since the button has mixed icon+text content
        $I->waitForElementVisible('//button[contains(., "Copy / Associate")]', 10);
        $I->click('//button[contains(., "Copy / Associate")]');
        $I->waitForElementVisible('.side-tree-panel', 30);
        $I->dontSeeElement('.comment-module');
    }

    public function dontSeeCommentsInCreateAssociationsTab(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Super User');
        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        // Use XPath since the button has mixed icon+text content
        $I->waitForElementVisible('//button[contains(., "Copy / Associate")]', 10);
        $I->click('//button[contains(., "Copy / Associate")]');
        $I->waitForElementVisible('.side-tree-panel', 30);
        $I->dontSeeElement('.comment-module');
    }

    public function deleteComment(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->createAComment('acceptance doc comment ' . sq($I->getItemId()));
        $I->waitForText('acceptance doc comment ' . sq($I->getItemId()), 10, '.comment-body');
        $I->see('acceptance doc comment ' . sq($I->getItemId()), '.comment-body');

        $I->click(['css' => '.comment-item:first-of-type .delete-btn']);
        $I->waitForElementVisible('#deleteCommentModal', 10);
        $I->click('#deleteCommentModal .btn-danger');
        $I->waitForElementNotVisible('//*[contains(text(),"acceptance doc comment ' . sq($I->getItemId()) . '")]', 30);
        $I->dontSee('acceptance doc comment ' . sq($I->getItemId()), '.comment-body');
    }

    public function deleteUpvotedDownvotedComment(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->createAComment('acceptance doc comment ' . sq($I->getItemId()));
        $I->waitForText('acceptance doc comment ' . sq($I->getItemId()), 10, '.comment-body');
        $I->see('acceptance doc comment ' . sq($I->getItemId()), '.comment-body');

        $loginPage->logout();
        $loginPage->loginAsRole('Admin');
        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->waitForElementVisible(['css' => '.comment-item:first-of-type .upvote-btn'], 30);

        $upvotesText = $I->grabTextFrom(['css' => '.comment-item:first-of-type .upvote-btn']);
        $upvotes = (int) trim($upvotesText);

        $I->click(['css' => '.comment-item:first-of-type .upvote-btn']);
        $I->waitForText((string)($upvotes + 1), 60, ['css' => '.comment-item:first-of-type .upvote-btn']);
        $I->see((string)($upvotes + 1), ['css' => '.comment-item:first-of-type .upvote-btn']);

        $loginPage->logout();
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementNotVisible('.spinner-border', 120);

        $I->waitForElement('.comment-module', 60);
        $I->waitForElement(['css' => '.comment-item:first-of-type .delete-btn'], 30);
        $I->click(['css' => '.comment-item:first-of-type .delete-btn']);
        $I->waitForElementVisible('#deleteCommentModal', 10);
        $I->click('#deleteCommentModal .btn-danger');
        $I->waitForElementNotVisible('//*[contains(text(),"acceptance doc comment ' . sq($I->getItemId()) . '")]', 30);
        $I->dontSee('acceptance doc comment ' . sq($I->getItemId()), '.comment-body');
    }
}
