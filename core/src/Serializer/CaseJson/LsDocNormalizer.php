<?php

declare(strict_types=1);

namespace App\Serializer\CaseJson;

use App\Entity\Framework\LsDoc;
use App\Service\Api1Uris;
use App\Util\Collection;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class LsDocNormalizer implements NormalizerInterface
{
    use DateCallbackTrait;
    use AssociationLinkTrait;
    use LinkUriTrait;
    use LastChangeDateTimeTrait;

    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private Api1Uris $api1Uris,
    ) {
    }

    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof LsDoc && null === ($context['generate-package'] ?? null);
    }

    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return [LsDoc::class => false];
    }

    #[\Override]
    public function normalize(mixed $data, ?string $format = null, array $context = []): ?array
    {
        if (!$data instanceof LsDoc) {
            return null;
        }

        $jsonLd = $context['case-json-ld'] ?? null;
        $addContext = (null !== $jsonLd) ? ($context['add-case-context'] ?? null) : null;
        $addType = $addContext ?? $context['add-case-type'] ?? null;
        $case10 = in_array('CASE-1.0', $context['groups'] ?? []);
        $subject = $data->getSubject();
        $subjectURIs = $data->getSubjects();
        $ret = [
            '@context' => (null !== $addContext)
                ? 'https://purl.imsglobal.org/spec/case/v1p0/context/imscasev1p0_context_v1p0.jsonld'
                : null,
            'type' => (null !== $addType)
                ? 'CFDocument'
                : null,
            'identifier' => $data->getIdentifier(),
            'uri' => $this->api1Uris->getUri($data),
            'caseVersion' => $case10 ? null : $data->getCaseVersion(),
            'frameworkType' => $case10 ? null : $data->getFrameworkType()?->getFrameworkType(),
            'creator' => $data->getCreator(),
            'title' => $data->getTitle(),
            'lastChangeDateTime' => $this->getLastChangeDateTime($data),
            'officialSourceURL' => $data->getOfficialUri(),
            'CFPackageURI' => $this->createPackageLinkUri($data, 'LsDoc', $context),
            'publisher' => $data->getPublisher(),
            'description' => $data->getDescription(),
            'subject' => ($subject ?? []) !== []
                ? $subject
                : null,
            'subjectURI' => count($subjectURIs) > 0
                ? $this->api1Uris->getLinkUriList($subjectURIs)
                : null,
            'language' => $data->getLanguage(),
            'version' => $data->getVersion(),
            'adoptionStatus' => $data->getAdoptionStatus(),
            'statusStartDate' => $this->toDate($data->getStatusStart()),
            'statusEndDate' => $this->toDate($data->getStatusEnd()),
            'licenseURI' => $this->api1Uris->getLinkUri($data->getLicence()),
            'notes' => $data->getNote(),
            'updatedAt' => in_array('updatedAt', $context['groups'] ?? [], true) ? $data->getUpdatedAt() : null,
            'associationSet' => $this->createAssociationLinks($data, $context),
            'extensions' => $case10 ? null : $data->getExtensions(),
        ];

        if (in_array('opensalt', $context['groups'] ?? [], true)) {
            $ret['_opensalt'] = $data->getExtra();
        }

        return Collection::removeEmptyElements($ret);
    }
}
