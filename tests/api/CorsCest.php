<?php

use Codeception\Util\HttpCode;

class CorsCest
{
    private const ORIGIN_SEND = 'http://salt-testing.com';
    private const ORIGIN_RECEIVE = '*';

    public function caseApiOptionsResponseHasCorsHeader(ApiTester $I): void
    {
        $I->haveHttpHeader('Origin', self::ORIGIN_SEND);
        $I->haveHttpHeader('Access-Control-Request-Method', 'GET');
        $I->sendOPTIONS('/ims/case/v1p0/CFDocuments');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeHttpHeader('Access-Control-Allow-Origin', self::ORIGIN_RECEIVE);
        $I->seeHttpHeader('Access-Control-Allow-Methods', 'GET');
        $I->seeHttpHeader('Access-Control-Allow-Headers');
    }

    public function caseApiGetResponseHasCorsHeader(ApiTester $I): void
    {
        $I->haveHttpHeader('Origin', self::ORIGIN_SEND);
        $I->sendGET('/ims/case/v1p0/CFDocuments');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeHttpHeader('Access-Control-Allow-Origin', self::ORIGIN_RECEIVE);
        $I->seeHttpHeader('Access-Control-Expose-Headers');
    }
}
