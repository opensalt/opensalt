<?php

declare(strict_types=1);

namespace Tests\Unit\App\Util;

use App\Util\RequestFormat;
use Symfony\Component\HttpFoundation\Request;

class RequestFormatTest extends \Codeception\Test\Unit
{
    public function testMarkdownExtensionSetsMdFormat(): void
    {
        $request = new Request();
        RequestFormat::determineRequestFormat($request, 'md');

        self::assertSame('md', $request->getRequestFormat());
    }

    public function testMarkdownAcceptHeaderSetsMdFormat(): void
    {
        $request = new Request();
        $request->headers->set('Accept', 'text/markdown');
        RequestFormat::determineRequestFormat($request, null);

        self::assertSame('md', $request->getRequestFormat());
    }

    public function testUnknownFormatFallsBackToJson(): void
    {
        $request = new Request();
        $request->headers->set('Accept', 'application/octet-stream');
        RequestFormat::determineRequestFormat($request, null);

        self::assertSame('json', $request->getRequestFormat());
    }

    public function testJsonAcceptStillWins(): void
    {
        $request = new Request();
        $request->headers->set('Accept', 'application/json');
        RequestFormat::determineRequestFormat($request, null);

        self::assertSame('json', $request->getRequestFormat());
    }
}
