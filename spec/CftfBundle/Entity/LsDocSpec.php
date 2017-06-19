<?php

namespace spec\CftfBundle\Entity;

use CftfBundle\Entity\LsDoc;
use PhpSpec\ObjectBehavior;

class LsDocSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(LsDoc::class);
    }
}
