<?php

namespace CftfBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EditorControllerTest extends WebTestCase
{
    public function testView()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/cf/1');
    }

    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/cf');
    }

}
