<?php

namespace App\Entity\Asn;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class AsnStandard
 *
 * @method AsnValue[]|ArrayCollection getType()
 * @method AsnValue[]|ArrayCollection getIdentifier()
 * @method AsnValue[]|ArrayCollection getIsPartOf()
 * @method AsnValue[]|ArrayCollection getAuthorityStatus()
 * @method AsnValue[]|ArrayCollection getIndexingStatus()
 * @method AsnValue[]|ArrayCollection getStatementNotation()
 * @method AsnValue[]|ArrayCollection getListID()
 * @method AsnValue[]|ArrayCollection getEducationLevel()
 * @method AsnValue[]|ArrayCollection getSubject()
 * @method AsnValue[]|ArrayCollection getAltStatementNotation()
 * @method AsnValue[]|ArrayCollection getStatementLabel()
 * @method AsnValue[]|ArrayCollection getDescription()
 * @method AsnValue[]|ArrayCollection getLanguage()
 * @method AsnValue[]|ArrayCollection getHasChild()
 * @method AsnValue[]|ArrayCollection getIsChildOf()
 * @method AsnValue[]|ArrayCollection getComment()
 * @method AsnValue[]|ArrayCollection getExactMatch()
 * @method AsnStandard setType(ArrayCollection $value)
 * @method AsnStandard setIdentifier(ArrayCollection $value)
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
 * @property AsnValue[]|ArrayCollection $educationLevel
 * @property AsnValue[]|ArrayCollection $subject
 * @property AsnValue[]|ArrayCollection $altStatementNotation
 * @property AsnValue[]|ArrayCollection $statementLabel
 * @property AsnValue[]|ArrayCollection $description
 * @property AsnValue[]|ArrayCollection $language
 * @property AsnValue[]|ArrayCollection $hasChild
 * @property AsnValue[]|ArrayCollection $isChildOf
 * @property AsnValue[]|ArrayCollection $comment
 * @property AsnValue[]|ArrayCollection $exactMatch
 */
class AsnStandard extends AsnBase
{
    public static $properties = [
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
