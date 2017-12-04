<?php

use Codeception\Scenario;
use Codeception\Util\Locator;
use Context\Login;
use Ramsey\Uuid\Uuid;

class SequenceNumberCest
{
    static public $docPath = '/cftree/doc/';

    public function importCSVSequenceNumber(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('super_user');
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->see('Import Children');
        $I->click('Import Children');
        $I->waitForElementVisible('#addChildrenModal');
        $I->see('Import Items');

        $data = file_get_contents(codecept_data_dir().'sequenceNumber.csv');

        $name = sq('SequenceNumberTest');
        $docUuid = Uuid::uuid4()->toString();
        $this->rememberedFramework = $name;

        $origValues = [
            'ab223660-6eed-4fcd-8f44-df61e15198e5',
            '3001b0a7-baec-4726-840c-8b65edb3cbd5',
        ];
        $replacements = [];
        $decoded = [];

        $lines = explode(PHP_EOL, $data);
        foreach ($lines as $i => $line) {
            if ($i == 0) {
                continue;
            }

            $decoded[] = str_getcsv($line);
        }

        foreach ($decoded as $item) {
            $replacements[] = Uuid::uuid4()->toString();
        }

        $data = str_replace($origValues, $replacements, $data);

        $filename = tempnam(codecept_data_dir(), 'tmp_eef_');
        unlink($filename);
        file_put_contents($filename.'.csv', $data);

        $I->attachFile('input#file-url', 'sequenceNumber.csv');
        $I->selectOption('#js-framework-to-association', array('value' => $I->getDocId()));
        $I->click('.btn-import-csv');
        $I->waitForJS('return $.active == 0;', 10);
        $I->getLastFrameworkId();
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->waitForElementVisible('#itemSection h4.itemTitle', 120);
        $I->executeJS("$('#tree1Section div.treeDiv').fancytree('getTree').visit(function(n){n.setExpanded(true);});");
        $I->waitForJS('return $.active == 0;', 1);
        $css = $I->grabTextFrom('.item-humanCodingScheme');
        $I->assertEquals($css, 'B');
    }
}
