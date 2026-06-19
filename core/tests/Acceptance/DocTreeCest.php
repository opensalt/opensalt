<?php

namespace Tests\Acceptance;

use Codeception\Scenario;
use Ramsey\Uuid\Uuid;
use Tests\Support\AcceptanceTester;
use Tests\Support\Context\Login;

class DocTreeCest
{
    public static $docPath = '/editor/';

    // tests
    public function verifyOrder(AcceptanceTester $I, Scenario $scenario)
    {
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Admin');
        $I->amOnPage('/cfdoc');
        $I->see('Import framework');
        $I->clickWithLeftButton(['css' => 'header a.dropdown-toggle svg[aria-label="Main Menu"]']);
        $I->click('Import framework');
        $I->waitForElementVisible('.modal');
        $I->see('Import CASE® file');
        $I->click('//*[@data-bs-target="#case"]');

        $data = file_get_contents(codecept_data_dir() . 'Ordering.json');

        $name = sq('OrderingTestFramework');
        $docUuid = Uuid::uuid4()->toString();

        $origValues = [
            'ACT Holistic Framework, Math',
            'a33fc64e-5c40-11e7-82c4-3d54268aa9ee',
        ];
        $replacements = [
            $name,
            $docUuid,
        ];

        $decoded = json_decode($data, true);
        foreach ($decoded['CFItems'] as $item) {
            $origValues[] = $item['identifier'];
            $replacements[] = Uuid::uuid4()->toString();
        }
        foreach ($decoded['CFAssociations'] as $item) {
            $origValues[] = $item['identifier'];
            $replacements[] = Uuid::uuid4()->toString();
        }

        $data = str_replace($origValues, $replacements, $data);

        $filename = tempnam(codecept_data_dir(), 'tmp_eef_');
        unlink($filename);
        file_put_contents($filename . '.json', $data);

        $I->attachFile('input#file-url', str_replace(codecept_data_dir(), '', $filename . '.json'));

        $I->click('.btn-import-case');
        $I->waitForJS('return ("function" === typeof $ && $.active == 0);', 30);

        $I::$staticLsDocId = $docUuid;
        $I->setDocId(null);
        $I->amOnPage('/editor/' . $docUuid);
        $I->waitForElementNotVisible('.spinner-border', 120);
        $I->waitForElementVisible('.details-panel .card-title', 180);
        $I->see($name);

        $I->executeJS("
            window.expandAllNodes = function() {
                const closed = document.querySelectorAll('.tree-node[aria-expanded=\"false\"] > .expand-control .expand-indicator:not(.expanding)');
                if (document.querySelectorAll('.tree-node[aria-expanded=\"false\"]').length === 0) {
                    window.nodesExpanded = true;
                    return;
                }
                closed.forEach(el => {
                    el.classList.add('expanding');
                    el.click();
                });
                setTimeout(window.expandAllNodes, 300);
            };
            window.nodesExpanded = false;
            window.expandAllNodes();
        ");
        $I->waitForJS('return window.nodesExpanded === true;', 30);

        $css = $I->grabMultiple('.coding-scheme');
        $array = [];
        $expectedArray = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24];

        foreach ($css as $cs) {
            $tmp = trim(str_replace(':', '', $cs));
            $tmp = explode('.', $tmp);
            $tmp = end($tmp);
            if (is_numeric($tmp)) {
                $array[] = (int)$tmp;
            }
        }

        $I->assertEquals($expectedArray, $array);
    }
}
