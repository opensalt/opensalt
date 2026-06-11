<?php

namespace Tests\Functional;

use App\Entity\User\AccessGroup;
use App\Entity\User\User;
use App\Service\User\UserManager;
use Codeception\Util\HttpCode;
use Tests\Support\FunctionalTester;

class MfaControllerCest
{
    private function createColonFreeUser(FunctionalTester $I): array
    {
        $em = $I->grabService('doctrine.orm.entity_manager');
        $orgRepo = $em->getRepository(AccessGroup::class);
        $org = $orgRepo->createQueryBuilder('o')
            ->where('o.name like :prefix')
            ->setParameter(':prefix', 'TEST:%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$org) {
            $faker = \Faker\Factory::create();
            $org = $orgRepo->addNewAccessGroup('TEST:'.str_replace("'", '', $faker->company()));
            $em->flush($org);
        }

        $faker = \Faker\Factory::create();
        $username = 'test2fa_'.$faker->userName();
        $password = $faker->password().'aB3';

        $userManager = $I->grabService(UserManager::class);
        $userManager->addNewUser($username, $org, $password, 'ROLE_SUPER_EDITOR', User::ACTIVE);
        $em->flush();

        $user = $em->getRepository(User::class)->findOneBy(['username' => $username]);

        return [$user, $password];
    }

    private function loginUser(FunctionalTester $I, User $user, string $password): void
    {
        $I->sendGet('/login');
        $I->seeResponseCodeIs(HttpCode::OK);

        $response = $I->grabResponse();
        preg_match('/name="_csrf_token"\s+value="([^"]+)"/', $response, $matches);
        $csrfToken = $matches[1] ?? '';

        $I->sendPost('/login', [
            '_username' => $user->getUserIdentifier(),
            '_password' => $password,
            '_csrf_token' => $csrfToken,
        ]);
    }

    private function createUserAndLogin(FunctionalTester $I): array
    {
        [$user, $password] = $this->createColonFreeUser($I);
        $this->loginUser($I, $user, $password);

        return [$user, $password];
    }

    private function enableTotpForUser(FunctionalTester $I, User $user): void
    {
        $em = $I->grabService('doctrine.orm.entity_manager');
        $user->setTotpSecret('JBSWY3DPEHPK3PXP');
        $user->setIsTotpEnabled(true);
        $em->flush();
    }

    public function testEnable2faPageShowsForAuthenticatedUser(FunctionalTester $I): void
    {
        $this->createUserAndLogin($I);

        $I->sendGet('/authentication/2fa/enable');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContains('2FA');
    }

    public function testReset2faConfirmPageShowsWhen2faEnabled(FunctionalTester $I): void
    {
        [$user] = $this->createUserAndLogin($I);
        $this->enableTotpForUser($I, $user);

        $I->sendGet('/authentication/2fa/reset');
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function testReset2faConfirmRedirectsWhen2faNotEnabled(FunctionalTester $I): void
    {
        $this->createUserAndLogin($I);

        $I->stopFollowingRedirects();
        $I->sendGet('/authentication/2fa/reset');
        $I->seeResponseCodeIs(HttpCode::FOUND);
        $I->seeHttpHeader('Location');
    }

    public function testReset2faDisables2fa(FunctionalTester $I): void
    {
        [$user] = $this->createUserAndLogin($I);
        $this->enableTotpForUser($I, $user);

        $I->sendPost('/authentication/2fa/reset');

        $I->seeInRepository(User::class, ['id' => $user->getId(), 'isTotpEnabled' => false]);
    }

    public function testEnable2faRequiresAuthentication(FunctionalTester $I): void
    {
        $I->stopFollowingRedirects();
        $I->sendGet('/authentication/2fa/enable');
        $I->seeResponseCodeIs(HttpCode::FOUND);
        $location = $I->grabHttpHeader('Location');
        $I->seeResponseContains('/login');
    }
}
