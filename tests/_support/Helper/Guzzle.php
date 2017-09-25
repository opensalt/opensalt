<?php

namespace Helper;

use Codeception\Module\WebDriver;
use Codeception\TestInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class Guzzle extends \Codeception\Module
{
    private $files = [];

    /**
     * @return WebDriver
     */
    public function getWebDriver(): WebDriver
    {
        return $this->getModule('WebDriver');
    }

    public function download(string $url): string
    {
        $baseUrl = $this->getWebDriver()->_getUrl();
        $session = $this->getWebDriver()->grabCookie('session');

        $domain = null;
        if (preg_match('#^(?:[a-z]+)://([^/]+)/#', $baseUrl, $matches)) {
            $domain = $matches[1];
        }

        $this->assertNotEmpty($domain, 'Could not find domain from WebDriver');

        $client = new Client([
            'base_uri' => $baseUrl,
            'timeout' => 10,
        ]);

        $headers = [
            'User-Agent' => 'OpenSALT Testing/1.0',
            'Accept' => 'application/json',
        ];

        $cookies = CookieJar::fromArray([
            'session' => $session,
        ], $domain);

        $savedFile = tempnam(codecept_output_dir(), 'download');
        $this->files[] = $savedFile;

        $response = $client->get($url, [
            'headers' => $headers,
            'cookies' => $cookies,
            'sink' => $savedFile,
        ]);

        $this->assertEquals(200, $response->getStatusCode(), "Download of {$url} failed.");

        return $savedFile;
    }

    public function _after(TestInterface $test)
    {
        parent::_after($test);

        foreach ($this->files as $file) {
            @unlink($file);
        }
    }
}
