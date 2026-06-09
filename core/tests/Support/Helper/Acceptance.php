<?php

namespace Tests\Support\Helper;

use Codeception\TestInterface;
use Tests\Support\AcceptanceTester;

class Acceptance extends \Codeception\Module
{
    public function _before(TestInterface $test): void
    {
        AcceptanceTester::$staticLsDocId = null;
        AcceptanceTester::$staticLsItemId = null;
    }
}
