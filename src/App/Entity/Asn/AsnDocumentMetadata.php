<?php

namespace App\Entity\Asn;

class AsnDocumentMetadata extends AsnBase
{
    public static $properties = [
        'primaryTopic' => 'http://xmlns.com/foaf/0.1/primaryTopic',
        'rightsHolder' => 'http://purl.org/dc/terms/rightsHolder',
        'modified' => 'http://purl.org/dc/terms/modified',
        'created' => 'http://purl.org/dc/terms/created',
        'licence' => 'http://creativecommons.org/ns#license',
        'attributionUrl' => 'http://creativecommons.org/ns#attributionURL',
        'attributionName' => 'http://creativecommons.org/ns#attributionName',
        'exportVersion' => 'http://purl.org/ASN/schema/core/exportVersion',
    ];
}
