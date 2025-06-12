<?php

declare(strict_types=1);

namespace App\Entity\Asn;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @method AsnValue[]|ArrayCollection|null getType()
 * @method AsnValue[]|ArrayCollection|null getJurisdiction()
 * @method AsnValue[]|ArrayCollection|null getTitle()
 * @method AsnValue[]|ArrayCollection|null getDescription()
 * @method AsnValue[]|ArrayCollection|null getSource()
 * @method AsnValue[]|ArrayCollection|null getLicence()
 * @method AsnValue[]|ArrayCollection|null getAttributionName()
 * @method AsnValue[]|ArrayCollection|null getPublicationStatus()
 * @method AsnValue[]|ArrayCollection|null getRepositoryDate()
 * @method AsnValue[]|ArrayCollection|null getDateCopyright()
 * @method AsnValue[]|ArrayCollection|null getValid()
 * @method AsnValue[]|ArrayCollection|null getTableOfContents()
 * @method AsnValue[]|ArrayCollection|null getSubject()
 * @method AsnValue[]|ArrayCollection|null getEducationLevel()
 * @method AsnValue[]|ArrayCollection|null getLanguage()
 * @method AsnValue[]|ArrayCollection|null getAuthor()
 * @method AsnValue[]|ArrayCollection|null getPublisher()
 * @method AsnValue[]|ArrayCollection|null getRights()
 * @method AsnValue[]|ArrayCollection|null getRightsHolder()
 * @method AsnValue[]|ArrayCollection|null getIdentifier()
 * @method AsnValue[]|ArrayCollection|null getHasChild()
 * @method AsnStandardDocument setType(ArrayCollection $value)
 * @method AsnStandardDocument setJurisdiction(ArrayCollection $value)
 * @method AsnStandardDocument setTitle(ArrayCollection $value)
 * @method AsnStandardDocument setDescription(ArrayCollection $value)
 * @method AsnStandardDocument setSource(ArrayCollection $value)
 * @method AsnStandardDocument setLicence(ArrayCollection $value)
 * @method AsnStandardDocument setAttributionName(ArrayCollection $value)
 * @method AsnStandardDocument setPublicationStatus(ArrayCollection $value)
 * @method AsnStandardDocument setRepositoryDate(ArrayCollection $value)
 * @method AsnStandardDocument setDateCopyright(ArrayCollection $value)
 * @method AsnStandardDocument setValid(ArrayCollection $value)
 * @method AsnStandardDocument setTableOfContents(ArrayCollection $value)
 * @method AsnStandardDocument setSubject(ArrayCollection $value)
 * @method AsnStandardDocument setEducationLevel(ArrayCollection $value)
 * @method AsnStandardDocument setLanguage(ArrayCollection $value)
 * @method AsnStandardDocument setAuthor(ArrayCollection $value)
 * @method AsnStandardDocument setPublisher(ArrayCollection $value)
 * @method AsnStandardDocument setRights(ArrayCollection $value)
 * @method AsnStandardDocument setRightsHolder(ArrayCollection $value)
 * @method AsnStandardDocument setIdentifier($value)
 * @method AsnStandardDocument setHasChild(ArrayCollection $value)
 *
 * @property AsnValue[]|ArrayCollection $type
 * @property AsnValue[]|ArrayCollection $jurisdiction
 * @property AsnValue[]|ArrayCollection $title
 * @property AsnValue[]|ArrayCollection $description
 * @property AsnValue[]|ArrayCollection $source
 * @property AsnValue[]|ArrayCollection $licence
 * @property AsnValue[]|ArrayCollection $attributionName
 * @property AsnValue[]|ArrayCollection $publicationStatus
 * @property AsnValue[]|ArrayCollection $repositoryDate
 * @property AsnValue[]|ArrayCollection $dateCopyright
 * @property AsnValue[]|ArrayCollection $valid
 * @property AsnValue[]|ArrayCollection $tableOfContents
 * @property AsnValue[]|ArrayCollection $subject
 * @property AsnValue[]|ArrayCollection $educationLevel
 * @property AsnValue[]|ArrayCollection $language
 * @property AsnValue[]|ArrayCollection $author
 * @property AsnValue[]|ArrayCollection $publisher
 * @property AsnValue[]|ArrayCollection $rights
 * @property AsnValue[]|ArrayCollection $rightsHolder
 * @property AsnValue[]|ArrayCollection $identifier
 * @property AsnValue[]|ArrayCollection $hasChild
 */
class AsnStandardDocument extends AsnBase
{
    public static array $properties = [
        'type' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
        'jurisdiction' => 'http://purl.org/ASN/schema/core/jurisdiction',
        'title' => 'http://purl.org/dc/elements/1.1/title',
        'description' => 'http://purl.org/dc/terms/description',
        'source' => 'http://purl.org/dc/terms/source',
        'licence' => 'http://creativecommons.org/ns#license',
        'attributionName' => 'http://creativecommons.org/ns#attributionName',
        'publicationStatus' => 'http://purl.org/ASN/schema/core/publicationStatus',
        'repositoryDate' => 'http://purl.org/ASN/schema/core/repositoryDate',
        'dateCopyright' => 'http://purl.org/dc/terms/dateCopyright',
        'valid' => 'http://purl.org/dc/terms/valid',
        'tableOfContents' => 'http://purl.org/dc/terms/tableOfContents',
        'subject' => 'http://purl.org/dc/terms/subject',
        'educationLevel' => 'http://purl.org/dc/terms/educationLevel',
        'language' => 'http://purl.org/dc/terms/language',
        'author' => 'http://www.loc.gov/loc.terms/relators/aut',
        'publisher' => 'http://purl.org/dc/elements/1.1/publisher',
        'rights' => 'http://purl.org/dc/terms/rights',
        'rightsHolder' => 'http://purl.org/dc/terms/rightsHolder',
        'identifier' => 'http://purl.org/ASN/schema/core/identifier',
        'hasChild' => 'http://purl.org/gem/qualifiers/hasChild',
    ];
}
