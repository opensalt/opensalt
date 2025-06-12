<?php

declare(strict_types=1);

namespace App\Entity\Asn;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @method AsnValue[]|ArrayCollection|null getType()
 * @method AsnValue[]|ArrayCollection|null getIdentifier()
 * @method AsnValue[]|ArrayCollection|null getIsPartOf()
 * @method AsnValue[]|ArrayCollection|null getAuthorityStatus()
 * @method AsnValue[]|ArrayCollection|null getIndexingStatus()
 * @method AsnValue[]|ArrayCollection|null getStatementNotation()
 * @method AsnValue[]|ArrayCollection|null getListID()
 * @method AsnValue[]|ArrayCollection|null getEducationLevel()
 * @method AsnValue[]|ArrayCollection|null getSubject()
 * @method AsnValue[]|ArrayCollection|null getAltStatementNotation()
 * @method AsnValue[]|ArrayCollection|null getStatementLabel()
 * @method AsnValue[]|ArrayCollection|null getDescription()
 * @method AsnValue[]|ArrayCollection|null getLanguage()
 * @method AsnValue[]|ArrayCollection|null getHasChild()
 * @method AsnValue[]|ArrayCollection|null getIsChildOf()
 * @method AsnValue[]|ArrayCollection|null getComment()
 * @method AsnValue[]|ArrayCollection|null getExactMatch()
 * @method AsnStandard setType(ArrayCollection $value)
 * @method AsnStandard setIdentifier($value)
 * @method AsnStandard setIsPartOf(ArrayCollection $value)
 * @method AsnStandard setAuthorityStatus(ArrayCollection $value)
 * @method AsnStandard setIndexingStatus(ArrayCollection $value)
 * @method AsnStandard setStatementNotation(ArrayCollection $value)
 * @method AsnStandard setListID(ArrayCollection $value)
 * @method AsnStandard setEducationLevel(ArrayCollection $value)
 * @method AsnStandard setSubject(ArrayCollection $value)
 * @method AsnStandard setAltStatementNotation(ArrayCollection $value)
 * @method AsnStandard setStatementLabel(ArrayCollection $value)
 * @method AsnStandard setDescription(ArrayCollection $value)
 * @method AsnStandard setLanguage(ArrayCollection $value)
 * @method AsnStandard setHasChild(ArrayCollection $value)
 * @method AsnStandard setIsChildOf(ArrayCollection $value)
 * @method AsnStandard setComment(ArrayCollection $value)
 * @method AsnStandard setExactMatch(ArrayCollection $value)
 *
 * @property AsnValue[]|ArrayCollection $type
 * @property AsnValue[]|ArrayCollection $identifier
 * @property AsnValue[]|ArrayCollection $isPartOf
 * @property AsnValue[]|ArrayCollection $authorityStatus
 * @property AsnValue[]|ArrayCollection $indexingStatus
 * @property AsnValue[]|ArrayCollection $statementNotation
 * @property AsnValue[]|ArrayCollection $listID
 * @property ArrayCollection<array-key, AsnValue>|null $educationLevel
 * @property AsnValue[]|ArrayCollection $subject
 * @property AsnValue[]|ArrayCollection $altStatementNotation
 * @property ArrayCollection<array-key, AsnValue>|null $statementLabel
 * @property AsnValue[]|ArrayCollection $description
 * @property ArrayCollection<array-key, AsnValue>|null $language
 * @property AsnValue[]|ArrayCollection $hasChild
 * @property AsnValue[]|ArrayCollection $isChildOf
 * @property AsnValue[]|ArrayCollection $comment
 * @property AsnValue[]|ArrayCollection $exactMatch
 */
class AsnStandard extends AsnBase
{
    public static array $properties = [
        'type' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
        'identifier' => 'http://purl.org/ASN/schema/core/identifier',
        'isPartOf' => 'http://purl.org/dc/terms/isPartOf',
        'authorityStatus' => 'http://purl.org/ASN/schema/core/authorityStatus',
        'indexingStatus' => 'http://purl.org/ASN/schema/core/indexingStatus',
        'statementNotation' => 'http://purl.org/ASN/schema/core/statementNotation',
        'listID' => 'http://purl.org/ASN/schema/core/listID',
        'educationLevel' => 'http://purl.org/dc/terms/educationLevel',
        'subject' => 'http://purl.org/dc/terms/subject',
        'altStatementNotation' => 'http://purl.org/ASN/schema/core/altStatementNotation',
        'statementLabel' => 'http://purl.org/ASN/schema/core/statementLabel',
        'description' => 'http://purl.org/dc/terms/description',
        'language' => 'http://purl.org/dc/terms/language',
        'hasChild' => 'http://purl.org/gem/qualifiers/hasChild',
        'isChildOf' => 'http://purl.org/gem/qualifiers/isChildOf',
        'comment' => 'http://purl.org/ASN/schema/core/comment',
        'exactMatch' => 'http://www.w3.org/2004/02/skos/core#exactMatch',
    ];
}
