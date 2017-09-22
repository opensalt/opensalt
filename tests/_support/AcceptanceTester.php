<?php

use Behat\Behat\Context\Context;
use CftfBundle\Entity\LsItem;
use Doctrine\ORM\EntityManager;
use CftfBundle\Entity\LsDoc;
use Facebook\WebDriver\WebDriverElement;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor implements Context
{
    use _generated\AcceptanceTesterActions;

    private $lsDocId = null;
    private $lsItemId = null;

    /**
     * @Given I am on the homepage
     */
    public function iAmOnTheHomepage(): AcceptanceTester
    {
        $this->amOnPage('/');

        return $this;
    }

    /**
     * @Then I should see :arg1
     */
    public function iShouldSee(string $arg1): AcceptanceTester
    {
        $this->see($arg1);

        return $this;
    }

    /**
     * @Then I should see :arg1 in the header
     */
    public function iShouldSeeInTheHeader(string $arg1): AcceptanceTester
    {
        $this->see($arg1, 'header');

        return $this;
    }

    /**
     * @Then I should see :arg1 in the :arg2 element
     */
    public function iShouldSeeInTheElement(string $arg1, string $arg2): AcceptanceTester
    {
        $this->see($arg1, $arg2);

        return $this;
    }

    /**
     * @When I follow :arg1
     */
    public function iFollow(string $arg1): AcceptanceTester
    {
        $this->click($arg1);

        return $this;
    }

    /**
     * @When I press :arg1
     * @When I click :arg1
     */
    public function iPress(string $link): AcceptanceTester
    {
        $this->click($link);

        return $this;
    }

    public function getLastFrameworkId()
    {
        /** @var EntityManager $em */
        $em = $this->grabService('doctrine.orm.default_entity_manager');

        $lsDocRepo = $em->getRepository(LsDoc::class);
        $lsDoc = $lsDocRepo->createQueryBuilder('d')
            ->orderBy('d.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$lsDoc) {
            $lsDoc = new LsDoc();
            $lsDoc->setTitle(sq('test framework'));
            $lsDoc->setCreator(sq('test creator'));

            $em->persist($lsDoc);
            $em->flush($lsDoc);
        }

        $this->lsDocId = $lsDoc->getId();

        return $this->lsDocId;
    }

    public function getDocId()
    {
        if (null === $this->lsDocId) {
            return $this->getLastFrameworkId();
        }

        return $this->lsDocId;
    }

    public function getLastItemId()
    {
        /** @var EntityManager $em */
        $em = $this->grabService('doctrine.orm.default_entity_manager');

        $lsItemRepo = $em->getRepository(LsItem::class);
        $lsItem = $lsItemRepo->createQueryBuilder('d')
            ->orderBy('d.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$lsItem) {
            $lsDocRepo = $em->getRepository(LsDoc::class);
            $doc = $lsDocRepo->find($this->getLastFrameworkId());

            /** @var LsItem $lsItem */
            $lsItem = $doc->createItem();
            $lsItem->setFullStatement(sq('test item'));
            $doc->createChildItem($lsItem);

            $em->persist($lsItem);
            $em->flush();
        }

        $this->lsItemId = $lsItem->getId();

        return $this->lsItemId;
    }

    public function getItemId()
    {
        if (null === $this->lsItemId) {
            return $this->getLastItemId();
        }

        return $this->lsItemId;
    }

    public function createAComment($content)
    {
        $this->click('.jquery-comments .commenting-field .textarea-wrapper .textarea');
        $this->fillField('.textarea', $content);
        $this->click('.jquery-comments .commenting-field .textarea-wrapper .control-row .send');
        $this->waitForElementChange('.comment-wrapper .wrapper .content', function(WebDriverElement $el) {
            return $el->isDisplayed();
        }, 2);
    }
}
