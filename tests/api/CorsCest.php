<?php

use Codeception\Util\HttpCode;

class CorsCest
{
    public function caseApiOptionsResponseHasCorsHeader(ApiTester $I)
    {
        $I->haveHttpHeader('Origin', 'http://salt-testing.com');
        $I->haveHttpHeader('Access-Control-Request-Method', 'GET');
        $I->sendOPTIONS('/ims/case/v1p0/CFDocuments');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeHttpHeader('Access-Control-Allow-Origin', '*');
        $I->seeHttpHeader('Access-Control-Allow-Methods', 'GET');
        $I->seeHttpHeader('Access-Control-Allow-Headers');
    }

    public function caseApiGetResponseHasCorsHeader(ApiTester $I)
    {
        $I->haveHttpHeader('Origin', 'http://salt-testing.com');
        $I->sendGET('/ims/case/v1p0/CFDocuments');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeHttpHeader('Access-Control-Allow-Origin', '*');
        $I->seeHttpHeader('Access-Control-Expose-Headers');
    }
}
