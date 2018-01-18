<?php

namespace CftfBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CopyControllerTest extends WebTestCase
{
    public function testFramework()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/Framework');
    }

}
