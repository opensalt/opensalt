<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;

readonly class RequestFormat
{
    public static function determineRequestFormat(
        Request $request,
        ?string $_format = null,
        array $allowedFormats = [
            'application/vnd.opensalt+json' => 'opensalt',
            'application/json' => 'json',
            'application/ld+json' => 'jsonld',
            'text/html' => 'html',
            'text/csv' => 'csv',
            'application/x-ndjson' => 'ndjson',
        ],
    ): void {
        if ($request->headers->has('x-opensalt')) {
            $request->setRequestFormat('opensalt');

            return;
        }

        if ('tree' === $_format || 'tree' === $request->query->get('display')) {
            $request->setRequestFormat('tree');

            return;
        }

        if (in_array($_format, $allowedFormats, true)) {
            $request->setRequestFormat($_format);

            return;
        }

        $useFormat = 'json';
        $quality = 0.0;

        $accept = AcceptHeader::fromString($request->headers->get('Accept'));
        $contentTypes = $accept->all();
        foreach ($contentTypes as $contentType) {
            $tryFormat = $request->getFormat($contentType->getValue());
            if (in_array($tryFormat, $allowedFormats, true)) {
                $useFormat = $tryFormat;
                $quality = $accept->get($contentType->getValue())?->getQuality() ?? 0.0;

                break;
            }
        }

        foreach ($allowedFormats as $contentType => $format) {
            $q = $accept->get($contentType)?->getQuality() ?? 0.0;
            if ($quality < $q) {
                $useFormat = $format;
                $quality = $q;
            }
        }

        $request->setRequestFormat($useFormat);
    }
}
