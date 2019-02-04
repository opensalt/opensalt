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
        if (preg_match('#^(?:https?)://([^/]+)/?#', $baseUrl, $matches)) {
            $domain = $matches[1];
        }

        $this->assertNotEmpty($domain, sprintf('Could not find domain from WebDriver [%s]', $baseUrl));

        $client = new Client([
            'base_uri' => $baseUrl,
            'timeout' => 60,
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

    public function fetchJson(string $url): array
    {
        return \GuzzleHttp\json_decode($this->fetch($url), true);
    }

    public function fetch(string $url, string $accept = 'application/json'): string
    {
        $baseUrl = $this->getWebDriver()->_getUrl();
        $session = $this->getWebDriver()->grabCookie('session');

        $domain = null;
        if (preg_match('#^(?:https?)://([^/]+)/?#', $baseUrl, $matches)) {
            $domain = $matches[1];
        }

        $this->assertNotEmpty($domain, sprintf('Could not find domain from WebDriver [%s]', $baseUrl));

        $client = new Client([
            'base_uri' => $baseUrl,
            'timeout' => 60,
        ]);

        $headers = [
            'User-Agent' => 'OpenSALT Testing/1.0',
            'Accept' => $accept,
        ];

        $cookies = CookieJar::fromArray([
            'session' => $session,
        ], $domain);

        $response = $client->get($url, [
            'headers' => $headers,
            'cookies' => $cookies,
        ]);

        $this->assertEquals(200, $response->getStatusCode(), "Fetch of {$url} failed.");

        return $response->getBody();
    }

    public function fetchRedirect(string $url): ?string
    {
        $baseUrl = $this->getWebDriver()->_getUrl();
        $session = $this->getWebDriver()->grabCookie('session');

        $domain = null;
        if (preg_match('#^(?:https?)://([^/]+)/?#', $baseUrl, $matches)) {
            $domain = $matches[1];
        }

        $this->assertNotEmpty($domain, sprintf('Could not find domain from WebDriver [%s]', $baseUrl));

        $client = new Client([
            'base_uri' => $baseUrl,
            'timeout' => 60,
        ]);

        $headers = [
            'User-Agent' => 'OpenSALT Testing/1.0',
            'Accept' => 'application/json',
        ];

        $cookies = CookieJar::fromArray([
            'session' => $session,
        ], $domain);

        $response = $client->get($url, [
            'headers' => $headers,
            'cookies' => $cookies,
            'allow_redirects' => false,
        ]);

        if (302 !== $response->getStatusCode() || !$response->hasHeader('Location')) {
            return null;
        }

        return current($response->getHeader('Location'));
    }

    public function _after(TestInterface $test)
    {
        parent::_after($test);

        foreach ($this->files as $file) {
            @unlink($file);
        }
    }
}
