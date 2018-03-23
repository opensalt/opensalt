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

    public function fetch(string $url): string
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

    public function pdf2text($filename)
    {
    // Read the data from pdf file
        $infile = @file_get_contents($filename, FILE_BINARY);
        if (empty($infile))
            return "";

        // Get all text data
        $transformations = array();
        $texts = array();

        // Get the list of all objects
        preg_match_all("#obj(.*)endobj#ismU", $infile, $objects);
        $objects = @$objects[1];

        // Select objects with streams
        for ($i = 0; $i < count($objects); $i++) {
            $currentObject = $objects[$i];

            // Check if an object includes data stream.
            if (preg_match("#stream(.*)endstream#ismU", $currentObject, $stream)) {
                $stream = ltrim($stream[1]);

                // Check object parameters and look for text data.
                $options = $this->getObjectOptions($currentObject);
                if (!(empty($options["Length1"]) && empty($options["Type"]) && empty($options["Subtype"])))
                    continue;

                // So, we have text data. Decode it
                $data = $this->getDecodedStream($stream, $options);
                if (strlen($data)) {
                    if (preg_match_all("#BT(.*)ET#ismU", $data, $textContainers)) {
                        $textContainers = @$textContainers[1];
                        $this->getDirtyTexts($texts, $textContainers);
                    } else
                        $this->getCharTransformations($transformations, $data);
                }
            }

        }

        // Analyze text blocks taking into account character transformations and return results.
        return $this->getTextUsingTransformations($texts, $transformations);
    }

    public function decodeAsciiHex($input) {
        $output = "";

        $isOdd = true;
        $isComment = false;

        for($i = 0, $codeHigh = -1; $i < strlen($input) && $input[$i] != '>'; $i++) {
            $c = $input[$i];

            if($isComment) {
                if ($c == '\r' || $c == '\n')
                    $isComment = false;
                continue;
            }

            switch($c) {
                case '\0': case '\t': case '\r': case '\f': case '\n': case ' ': break;
                case '%':
                    $isComment = true;
                break;

                default:
                    $code = hexdec($c);
                    if($code === 0 && $c != '0')
                        return "";

                    if($isOdd)
                        $codeHigh = $code;
                    else
                        $output .= chr($codeHigh * 16 + $code);

                    $isOdd = !$isOdd;
                break;
            }
        }

        if($input[$i] != '>')
            return "";

        if($isOdd)
            $output .= chr($codeHigh * 16);

        return $output;
    }

    public function decodeAscii85($input) {
        $output = "";

        $isComment = false;
        $ords = array();

        for($i = 0, $state = 0; $i < strlen($input) && $input[$i] != '~'; $i++) {
            $c = $input[$i];

            if($isComment) {
                if ($c == '\r' || $c == '\n')
                    $isComment = false;
                continue;
            }

            if ($c == '\0' || $c == '\t' || $c == '\r' || $c == '\f' || $c == '\n' || $c == ' ')
                continue;
            if ($c == '%') {
                $isComment = true;
                continue;
            }
            if ($c == 'z' && $state === 0) {
                $output .= str_repeat(chr(0), 4);
                continue;
            }
            if ($c < '!' || $c > 'u')
                return "";

            $code = ord($input[$i]) & 0xff;
            $ords[$state++] = $code - ord('!');

            if ($state == 5) {
                $state = 0;
                for ($sum = 0, $j = 0; $j < 5; $j++)
                    $sum = $sum * 85 + $ords[$j];
                for ($j = 3; $j >= 0; $j--)
                    $output .= chr($sum >> ($j * 8));
            }
        }
        if ($state === 1)
            return "";
        elseif ($state > 1) {
            for ($i = 0, $sum = 0; $i < $state; $i++)
                $sum += ($ords[$i] + ($i == $state - 1)) * pow(85, 4 - $i);
            for ($i = 0; $i < $state - 1; $i++)
                $ouput .= chr($sum >> ((3 - $i) * 8));
        }

        return $output;
    }

    public function decodeFlate($input) {
        return @gzuncompress($input);
    }

    public function getObjectOptions($object) {
        $options = array();
        if (preg_match("#<<(.*)>>#ismU", $object, $options)) {
            $options = explode("/", $options[1]);
            @array_shift($options);

            $o = array();
            for ($j = 0; $j < @count($options); $j++) {
                $options[$j] = preg_replace("#\s+#", " ", trim($options[$j]));
                if (strpos($options[$j], " ") !== false) {
                    $parts = explode(" ", $options[$j]);
                    $o[$parts[0]] = $parts[1];
                } else
                    $o[$options[$j]] = true;
            }
            $options = $o;
            unset($o);
        }

        return $options;
    }

    public function getDecodedStream($stream, $options) {
        $data = "";
        if (empty($options["Filter"]))
            $data = $stream;
        else {
            $length = !empty($options["Length"]) ? $options["Length"] : strlen($stream);
            $_stream = substr($stream, 0, $length);

            foreach ($options as $key => $value) {
                if ($key == "ASCIIHexDecode")
                    $_stream = $this->decodeAsciiHex($_stream);
                if ($key == "ASCII85Decode")
                    $_stream = $this->decodeAscii85($_stream);
                if ($key == "FlateDecode")
                    $_stream = $this->decodeFlate($_stream);
            }
            $data = $_stream;
        }
        return $data;
    }

    public function getDirtyTexts(&$texts, $textContainers) {
        for ($j = 0; $j < count($textContainers); $j++) {
            if (preg_match_all("#\[(.*)\]\s*TJ#ismU", $textContainers[$j], $parts))
                $texts = array_merge($texts, @$parts[1]);
            elseif(preg_match_all("#Td\s*(\(.*\))\s*Tj#ismU", $textContainers[$j], $parts))
                $texts = array_merge($texts, @$parts[1]);
        }
    }

    public function getCharTransformations(&$transformations, $stream) {
        preg_match_all("#([0-9]+)\s+beginbfchar(.*)endbfchar#ismU", $stream, $chars, PREG_SET_ORDER);
        preg_match_all("#([0-9]+)\s+beginbfrange(.*)endbfrange#ismU", $stream, $ranges, PREG_SET_ORDER);

        for ($j = 0; $j < count($chars); $j++) {
            $count = $chars[$j][1];
            $current = explode("\n", trim($chars[$j][2]));
            for ($k = 0; $k < $count && $k < count($current); $k++) {
                if (preg_match("#<([0-9a-f]{2,4})>\s+<([0-9a-f]{4,512})>#is", trim($current[$k]), $map))
                    $transformations[str_pad($map[1], 4, "0")] = $map[2];
            }
        }
        for ($j = 0; $j < count($ranges); $j++) {
            $count = $ranges[$j][1];
            $current = explode("\n", trim($ranges[$j][2]));
            for ($k = 0; $k < $count && $k < count($current); $k++) {
                if (preg_match("#<([0-9a-f]{4})>\s+<([0-9a-f]{4})>\s+<([0-9a-f]{4})>#is", trim($current[$k]), $map)) {
                    $from = hexdec($map[1]);
                    $to = hexdec($map[2]);
                    $_from = hexdec($map[3]);

                    for ($m = $from, $n = 0; $m <= $to; $m++, $n++)
                        $transformations[sprintf("%04X", $m)] = sprintf("%04X", $_from + $n);
                } elseif (preg_match("#<([0-9a-f]{4})>\s+<([0-9a-f]{4})>\s+\[(.*)\]#ismU", trim($current[$k]), $map)) {
                    $from = hexdec($map[1]);
                    $to = hexdec($map[2]);
                    $parts = preg_split("#\s+#", trim($map[3]));

                    for ($m = $from, $n = 0; $m <= $to && $n < count($parts); $m++, $n++)
                        $transformations[sprintf("%04X", $m)] = sprintf("%04X", hexdec($parts[$n]));
                }
            }
        }
    }

    public function getTextUsingTransformations($texts, $transformations) {
        $document = "";
        for ($i = 0; $i < count($texts); $i++) {
            $isHex = false;
            $isPlain = false;

            $hex = "";
            $plain = "";
            for ($j = 0; $j < strlen($texts[$i]); $j++) {
                $c = $texts[$i][$j];
                switch($c) {
                    case "<":
                        $hex = "";
                        $isHex = true;
                    break;
                    case ">":
                        $hexs = str_split($hex, 4);
                        for ($k = 0; $k < count($hexs); $k++) {
                            $chex = str_pad($hexs[$k], 4, "0");
                            if (isset($transformations[$chex]))
                                $chex = $transformations[$chex];
                            $document .= html_entity_decode("&#x".$chex.";");
                        }
                        $isHex = false;
                    break;
                    case "(":
                        $plain = "";
                        $isPlain = true;
                    break;
                    case ")":
                        $document .= $plain;
                        $isPlain = false;
                    break;
                    case "\\":
                        $c2 = $texts[$i][$j + 1];
                        if (in_array($c2, array("\\", "(", ")"))) $plain .= $c2;
                        elseif ($c2 == "n") $plain .= '\n';
                        elseif ($c2 == "r") $plain .= '\r';
                        elseif ($c2 == "t") $plain .= '\t';
                        elseif ($c2 == "b") $plain .= '\b';
                        elseif ($c2 == "f") $plain .= '\f';
                        elseif ($c2 >= '0' && $c2 <= '9') {
                            $oct = preg_replace("#[^0-9]#", "", substr($texts[$i], $j + 1, 3));
                            $j += strlen($oct) - 1;
                            $plain .= html_entity_decode("&#".octdec($oct).";");
                        }
                        $j++;
                    break;

                    default:
                        if ($isHex)
                            $hex .= $c;
                        if ($isPlain)
                            $plain .= $c;
                    break;
                }
            }
            $document .= "\n";
        }

        return $document;
    }
}
