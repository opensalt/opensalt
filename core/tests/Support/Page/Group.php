<?php

namespace Tests\Support\Page;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Tests\Support\AcceptanceTester;

class Group implements Context
{
    public const string GROUP_ADMIN_PAGE = '/admin/access_group/';

    protected ?string $groupName;

    public function __construct(protected AcceptanceTester $I)
    {
    }

    /**
     * @Then /^I add a Group$/
     */
    public function iAddAGroup()
    {
        $I = $this->I;
        /** @var \Faker\Generator $faker */
        $faker = \Faker\Factory::create();
        $name = str_replace("'", '', $faker->company());
        $this->groupName = $name;

        $I->click('.dropdown-toggle');
        $I->click('Manage access groups');
        $I->see('Group list', 'h1');
        $I->click('Add a new access group');
        $I->fillField('#salt_userbundle_organization_name', $name);
        $I->click('Add');
        $I->remember('lastNewOrg', $this->groupName);
    }

    /**
     * @Then /^I delete the Group$/
     */
    public function iDeleteTheGroup()
    {
        $I = $this->I;
        $org = $this->groupName;

        $I->amOnPage(self::GROUP_ADMIN_PAGE);
        $I->scrollTo("//td[text()='{$org}']/..//a[text()='show']", 54, 0);
        $I->click("//td[text()='{$org}']/..//a[text()='show']");
        $I->see($this->groupName);
        $I->click('Delete');
        $I->remember('lastDeletedOrg', $this->groupName);
    }

    /**
     * @Given /^I am on the Groups list page$/
     */
    public function iAmOnTheGroupsListPage()
    {
        $I = $this->I;

        $I->amOnPage(self::GROUP_ADMIN_PAGE);
        $I->see('Group list', 'h1');
    }

    /**
     * @Given /^I should be on the Groups list page$/
     */
    public function iShouldBeOnTheGroupsListPage()
    {
        $I = $this->I;

        $I->seeInCurrentUrl(self::GROUP_ADMIN_PAGE);
        $I->see('Group list', 'h1');
    }

    /**
     * @Then /^I should see the following:$/
     */
    public function iShouldSeeTheFollowing(TableNode $table)
    {
        $I = $this->I;

        $rows = $table->getRows();
        foreach ($rows as $row) {
            $I->see($row[0]);
        }
    }

    /**
     * @Then /^I should see the Group$/
     */
    public function iShouldSeeTheGroup()
    {
        $I = $this->I;

        $I->amOnPage(self::GROUP_ADMIN_PAGE);
        $I->see($this->groupName);
    }

    /**
     * @Then /^I edit the name in Group$/
     */
    public function iEditTheNameInGroup(TableNode $table)
    {
        $I = $this->I;
        $org = $this->groupName;

        $I->amOnPage(self::GROUP_ADMIN_PAGE);
        $I->scrollTo("//td[text()='{$org}']/..//a[text()='edit']", 100, 0);
        $I->click("//td[text()='{$org}']/..//a[text()='edit']");
        $I->seeInField('#salt_userbundle_organization_name', $this->groupName);
        $rows = $table->getRows();
        foreach ($rows as $row) {
            $I->fillField('#salt_userbundle_organization_name', $row[0]);
            $I->click('Save');
            $I->waitForText($row[0], 10);
            $I->see($row[0]);
            $this->groupName = $row[0];
        }
    }

    /**
     * @Then /^I change the name of the Group$/
     */
    public function iChangeGroupName()
    {
        /** @var \Faker\Generator $faker */
        $faker = \Faker\Factory::create();
        $name = str_replace("'", '', $faker->company());
        $newOrgName = $name;

        $I = $this->I;
        $org = $this->groupName;

        $I->amOnPage(self::GROUP_ADMIN_PAGE);
        $I->scrollTo("//td[text()='{$org}']/..//a[text()='edit']", 54, 0);
        $I->click("//td[text()='{$org}']/..//a[text()='edit']");
        //$I->amOnPage($I->grabAttributeFrom("//td[text()='{$org}']/..//a[text()='edit']", 'href'));
        $I->seeInField('#salt_userbundle_organization_name', $this->groupName);
        $I->fillField('#salt_userbundle_organization_name', $newOrgName);
        $I->click('Save');
        $I->waitForText($newOrgName, 10);
        $I->see($newOrgName);
        $this->groupName = $newOrgName;
        $I->remember('lastChangedOrg', $this->groupName);
    }

    /**
     * @Then /^I edit the new Group$/
     */
    public function iEditTheNewGroup()
    {
        $I = $this->I;

        $org = $this->groupName;
        $I->amOnPage(self::GROUP_ADMIN_PAGE);
        $I->click("//td[text()='{$org}']/..//a[text()='edit']");
    }

    /**
     * @Then /^I show the new Group$/
     */
    public function iShowTheNewGroup()
    {
        $I = $this->I;

        $org = $this->groupName;
        $I->amOnPage(self::GROUP_ADMIN_PAGE);
        $I->scrollTo("//td[text()='{$org}']/..//a[text()='show']", 54, 0);
        $I->click("//td[text()='{$org}']/..//a[text()='show']");
    }
}
