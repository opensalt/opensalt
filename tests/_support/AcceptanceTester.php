<?php

use Behat\Behat\Context\Context;
use Doctrine\ORM\EntityManager;
use Salt\UserBundle\Entity\Organization;
use Salt\UserBundle\Entity\User;
use CftfBundle\Entity\LsDoc;

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
            $lsDoc->setTitle('test framework');
            $lsDoc->setCreator('test creator');

            $em->persist($lsDoc);
            $em->flush($lsDoc);
        }

        $this->lsDocId = $lsDoc->getId();
    }

    public function getDocId()
    {
        return $this->lsDocId;
    }
}
