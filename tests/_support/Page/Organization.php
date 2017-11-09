<?php

namespace Page;

use Behat\Behat\Context\Context;

class Organization implements Context {

  /**
   * @var \AcceptanceTester
   */
  protected $I;

  public function __construct(\AcceptanceTester $I)
  {
    $this->I = $I;
  }

}