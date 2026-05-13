<?php

namespace Tests\Acceptance;

use Codeception\Scenario;
use Tests\Support\AcceptanceTester;
use Tests\Support\Context\Login;

class CommentDocCest
{
    public static $docPath = '/editor/';

    public function _before(AcceptanceTester $I)
    {
        $I->assertFeatureEnabled('comments');
    }

    // tests
    public function seeCommentsSectionAsAnAnonymousUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $I->amOnPage(self::$docPath . $I->getDocId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->waitForElementVisible('.comment-module', 30);
        $I->seeElement('.comment-module');
        $I->waitForElementVisible('.login-prompt', 10);
        $I->see('Log in to post comments.', '.login-prompt');
    }

    public function dontSeeCommentsFormAsAnAnonymousUser(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $I->amOnPage(self::$docPath . $I->getDocId());
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
        $I->amOnPage(self::$docPath . $I->getDocId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->waitForElementVisible('.comment-textarea', 30);
        $I->seeElement('.comment-textarea');
    }

    public function commentAsAnAuthenticatedUser(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$docPath . $I->getDocId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->createAComment('acceptance doc comment ' . sq($I->getDocId()));
        $I->waitForText('acceptance doc comment ' . sq($I->getDocId()), 10, ['css' => '.comment-item:first-of-type .comment-body']);
        $I->see('acceptance doc comment ' . sq($I->getDocId()), ['css' => '.comment-item:first-of-type .comment-body']);

        // Verify a different user can see the comment
        $loginPage->logout();
        $I->amOnPage(self::$docPath . $I->getDocId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->waitForText('acceptance doc comment ' . sq($I->getDocId()), 10, ['css' => '.comment-item:first-of-type .comment-body']);
        $I->see('acceptance doc comment ' . sq($I->getDocId()), ['css' => '.comment-item:first-of-type .comment-body']);
    }

    public function upvoteOrDownvoteAsAnAnonymousUser(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();

        // Create a comment first to ensure there's something to upvote
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$docPath . $I->getDocId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->createAComment('doc comment to upvote anonymous');
        $loginPage->logout();

        $I->amOnPage(self::$docPath . $I->getDocId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->waitForElementVisible(['css' => '.comment-item:first-of-type .upvote-btn'], 10);
        $I->seeElement(['css' => '.comment-item:first-of-type .upvote-btn[disabled]']);
    }

    public function upvoteAsAnAuthenticatedUser(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$docPath . $I->getDocId());
        $I->waitForElementNotVisible('.spinner-border', 120);

        // Ensure there is a comment
        $I->createAComment('doc comment to upvote auth');

        $I->waitForElementVisible(['css' => '.comment-item:first-of-type .upvote-btn'], 10);
        $upvotesText = $I->grabTextFrom(['css' => '.comment-item:first-of-type .upvote-btn']);
        $upvotes = (int) trim($upvotesText);
        $I->click(['css' => '.comment-item:first-of-type .upvote-btn']);
        $I->waitForText((string)($upvotes + 1), 10, ['css' => '.comment-item:first-of-type .upvote-btn .upvote-count']);
    }

    public function downvoteAsAnAuthenticatedUser(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$docPath . $I->getDocId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->createAComment('downvote doc comment ' . sq($I->getDocId()));
        $I->waitForElementVisible(['css' => '.comment-item:first-of-type .upvote-btn'], 10);
        $I->click(['css' => '.comment-item:first-of-type .upvote-btn']);
        $I->wait(1);
        $upvotesText = $I->grabTextFrom(['css' => '.comment-item:first-of-type .upvote-btn']);
        $upvotes = (int) trim($upvotesText);
        $I->click(['css' => '.comment-item:first-of-type .upvote-btn']);
        // Sometimes wait isn't enough, but it should decrement
        if ($upvotes > 1) {
            $I->waitForText((string)($upvotes - 1), 10, ['css' => '.comment-item:first-of-type .upvote-btn .upvote-count']);
        } else {
            $I->waitForElementNotVisible(['css' => '.comment-item:first-of-type .upvote-btn .upvote-count'], 10);
        }
    }

    public function dontSeeCommentsInCopyItemsTab(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Super User');
        $I->amOnPage(self::$docPath . $I->getDocId());
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
        $I->amOnPage(self::$docPath . $I->getDocId());
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
        $I->amOnPage(self::$docPath . $I->getDocId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->createAComment('acceptance doc comment ' . sq($I->getDocId()));
        $I->waitForText('acceptance doc comment ' . sq($I->getDocId()), 10, '.comment-body');
        $I->see('acceptance doc comment ' . sq($I->getDocId()), '.comment-body');

        $I->click(['css' => '.comment-item:first-of-type .delete-btn']);
        $I->waitForElementVisible('#deleteCommentModal', 10);
        $I->click('#deleteCommentModal .btn-danger');
        $I->waitForElementNotVisible('//*[contains(text(),"acceptance doc comment ' . sq($I->getDocId()) . '")]', 30);
        $I->dontSee('acceptance doc comment ' . sq($I->getDocId()), '.comment-body');
    }

    public function deleteUpvotedDownvotedComment(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$docPath . $I->getDocId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->createAComment('acceptance doc comment ' . sq($I->getDocId()));
        $I->waitForText('acceptance doc comment ' . sq($I->getDocId()), 10, '.comment-body');
        $I->see('acceptance doc comment ' . sq($I->getDocId()), '.comment-body');

        $loginPage->logout();
        $loginPage->loginAsRole('Admin');
        $I->amOnPage(self::$docPath . $I->getDocId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->waitForElementVisible(['css' => '.comment-item:first-of-type .upvote-btn'], 60);

        $upvotesText = $I->grabTextFrom(['css' => '.comment-item:first-of-type .upvote-btn']);
        $upvotes = (int) trim($upvotesText);

        $I->click(['css' => '.comment-item:first-of-type .upvote-btn']);
        $I->waitForElementVisible(['css' => '.comment-item:first-of-type .upvote-btn .upvote-count'], 30);
        $I->waitForText((string)($upvotes + 1), 10, ['css' => '.comment-item:first-of-type .upvote-btn .upvote-count']);

        $loginPage->logout();
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$docPath . $I->getDocId());
        $I->waitForElementNotVisible('.spinner-border', 120);

        $I->waitForElementVisible(['css' => '.comment-item:first-of-type .delete-btn'], 30);
        $I->click(['css' => '.comment-item:first-of-type .delete-btn']);
        $I->waitForElementVisible('#deleteCommentModal', 10);
        $I->click('#deleteCommentModal .btn-danger');
        $I->waitForElementNotVisible('//*[contains(text(),"acceptance doc comment ' . sq($I->getDocId()) . '")]', 30);
    }

    public function deleteRepliedComment(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$docPath . $I->getDocId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->createAComment('acceptance doc replied comment ' . sq($I->getDocId()));
        $I->wait(1);
        $I->see('acceptance doc replied comment ' . sq($I->getDocId()), '.comment-body');

        $loginPage->logout();
        $loginPage->loginAsRole('Admin');
        $I->amOnPage(self::$docPath . $I->getDocId());
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->wait(1);

        $I->waitForElementVisible(['css' => '.comment-item:first-of-type .reply-btn'], 30);
        $I->click(['css' => '.comment-item:first-of-type .reply-btn']);
        $I->submitReply('reply');

        $loginPage->logout();
        $loginPage->loginAsRole('Editor');
        $I->amOnPage(self::$docPath . $I->getDocId());
        $I->waitForElementNotVisible('.spinner-border', 120);

        $I->waitForElementVisible(['css' => '.comment-item:first-of-type .delete-btn'], 30);
        $I->click(['css' => '.comment-item:first-of-type .delete-btn']);
        $I->waitForElementVisible('#deleteCommentModal', 10);
        $I->click('#deleteCommentModal .btn-danger');
        $I->waitForElementNotVisible('//*[contains(text(),"acceptance doc replied comment ' . sq($I->getDocId()) . '")]', 30);
    }
}
