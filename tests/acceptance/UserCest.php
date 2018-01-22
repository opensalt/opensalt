<?php

use Salt\UserBundle\Entity\User;
use Salt\UserBundle\Entity\Organization;

class UserCest
{
    public function pendingStatus(AcceptanceTester $I)
    {
        $faker = \Faker\Factory::create();
        $username = $faker->email;

        $orgId = $I->haveInRepository(Organization::class, array('name' => $faker->company));
        $org = $I->grabEntityFromRepository(Organization::class, array('id' => $orgId));

        $I->persistEntity(new User(), array('username' => $username, 'org' => $org));
        $I->seeInRepository(User::class, ['username' => $username, 'roles' => '["ROLE_VIEWER"]', 'status' => 2]);
    }
}
