<?php


class DocTreeCest
{
    static public $docPath = '/cftree/doc/';

    // tests
    public function verifyOrder(AcceptanceTester $I)
    {
        $I->getLastFrameworkId();
        $I->amOnPage(self::$docPath.$I->getDocId());

        $I->see('ACT Holistic Framework, Math');
    }
}
