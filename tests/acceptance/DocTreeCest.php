<?php

class DocTreeCest
{
    static public $docPath = '/cftree/doc/';

    // tests
    public function verifyOrder(AcceptanceTester $I)
    {
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('Admin');
        $I->amOnPage('/cfdoc');
        $I->see('Import framework');
        $I->click('Import framework');
        $I->waitForElementVisible('.modal');
        $I->see('Import CASE file');
        $I->click('//*[@href="#case"]');
        $I->attachFile('#file-url', 'Ordering.json');
        $I->click('.btn-import-case');
        $I->waitForJS('return $.active == 0;', 10);

        $I->getLastFrameworkId();
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->see('ACT Holistic Framework, Math');
        $I->executeJS("$('#tree1Section div.treeDiv').fancytree('getTree').visit(function(n){n.setExpanded(true);});");
        $I->waitForJS('return $.active == 0;', 1);
        $css = $I->grabMultiple('.item-humanCodingScheme');
        $array = [];
        $expectedArray = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24];

        foreach($css as $cs) {
            $tmp = explode('.', $cs);
            $tmp = end($tmp);
            if (is_numeric($tmp)) {
                $array[] = intval($tmp);
            }
        }

        $I->assertEquals($array, $expectedArray);
    }
}
