<?php

namespace App\Serializer\CaseJson;

use App\Entity\Framework\LsItem;
use App\Service\Api1Uris;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

final class LsItemNormalizer implements ContextAwareNormalizerInterface
{
    use DateCallbackTrait;
    use AssociationLinkTrait;
    use LinkUriTrait;
    use LastChangeDateTimeTrait;

    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly Api1Uris $api1Uris,
    ) {
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof LsItem;
    }

    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        if (!$object instanceof LsItem) {
            return null;
        }

        $jsonLd = $context['case-json-ld'] ?? null;
        $addContext = (null !== $jsonLd) ? ($context['add-case-context'] ?? null) : null;
        $conceptKeywords = $object->getConceptKeywordsArray();
        $conceptKeywordsUri = $object->getConcepts();
        $data = [
            '@context' => (null !== $addContext)
                ? 'https://purl.imsglobal.org/spec/case/v1p0/context/imscasev1p0_context_v1p0.jsonld'
                : null,
            'type' => (null !== $addContext)
                ? 'CFItem'
                : null,
            'identifier' => $object->getIdentifier(),
            'uri' => $this->api1Uris->getUri($object),
            'CFDocumentURI' => $this->createDocumentLinkUri($object->getLsDoc(), 'LsItem', $context),
            'fullStatement' => $object->getFullStatement(),
            'alternativeLabel' => $object->getAlternativeLabel(),
            'CFItemType' => $object->getItemType()?->getTitle(),
            'CFItemTypeURI' => $this->api1Uris->getLinkUri($object->getItemType()),
            'humanCodingScheme' => $object->getHumanCodingScheme(),
            'listEnumeration' => $object->getListEnumInSource(),
            'abbreviatedStatement' => $object->getAbbreviatedStatement(),
            'conceptKeywords' => count($conceptKeywords) > 0
                ? $conceptKeywords
                : null,
            'conceptKeywordsURI' => count($conceptKeywordsUri) > 0
                ? $this->api1Uris->getLinkUri($conceptKeywordsUri[0])
                : null,
            'notes' => $object->getNotes(),
            'language' => $object->getLanguage(),
            'educationLevel' => $this->api1Uris->splitByComma($object->getEducationalAlignment()),
            'licenseURI' => $this->api1Uris->getLinkUri($object->getLicence()),
            'statusStartDate' => $this->toDate($object->getStatusStart()),
            'statusEndDate' => $this->toDate($object->getStatusEnd()),
            'lastChangeDateTime' => $this->getLastChangeDateTime($object),
            'associationSet' => $this->createAssociationLinks($object, $context),
        ];

        return array_filter($data, static fn ($val) => null !== $val);
    }
}
