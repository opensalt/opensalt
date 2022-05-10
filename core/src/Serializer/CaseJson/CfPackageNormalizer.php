<?php

namespace App\Serializer\CaseJson;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsDocRepository;
use App\Security\Permission;
use App\Service\Api1Uris;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class CfPackageNormalizer implements NormalizerAwareInterface, ContextAwareNormalizerInterface
{
    use NormalizerAwareTrait;

    public function __construct(
        private readonly Api1Uris $api1Uris,
        private readonly LsDocRepository $docRepository,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof LsDoc && null !== ($context['generate-package'] ?? null);
    }

    public function normalize(mixed $object, string $format = null, array $context = []): ?array
    {
        if (!$object instanceof LsDoc) {
            return null;
        }

        $jsonLd = $context['case-json-ld'] ?? null;
        $addContext = (null !== $jsonLd) ? ($context['add-case-context'] ?? null) : null;
        unset($context['add-case-context'], $context['generate-package']);
        $context['no-association-links'] = true;
        $data = [
            '@context' => (null !== $addContext)
                ? 'https://purl.imsglobal.org/spec/case/v1p0/context/imscasev1p0_context_v1p0.jsonld'
                : null,
            'uri' => (null !== $jsonLd)
                ? $this->api1Uris->getUri($object, 'api_v1p0_cfpackage')
                : null,
            'type' => (null !== $jsonLd)
                ? 'CFPackage'
                : null,
            'CFDocument' => $this->normalizer->normalize($object, $format, $context),
        ];

        $package = $this->docRepository->getPackageArray($object);

        foreach ($package['CFItems'] as $obj) {
            $data['CFItems'][] = $this->normalizer->normalize($obj, $format, $context);
        }

        /** @var LsAssociation $obj */
        foreach ($package['CFAssociations'] as $obj) {
            if (!$this->canListDocument($obj, 'origin') ||
                !$this->canListDocument($obj, 'destination')) {
                // Remove associations to frameworks one can't normally see
                continue;
            }

            $data['CFAssociations'][] = $this->normalizer->normalize($obj, $format, $context);
        }

        foreach ($package['CFDefinitions'] as $defType => $defs) {
            foreach ($defs as $obj) {
                $data['CFDefinitions'][$defType][] = $this->normalizer->normalize($obj, $format, $context);
            }
        }

        if (!empty($package['CFRubrics'])) {
            foreach ($package['CFRubrics'] as $obj) {
                $data['CFRubrics'][] = $this->normalizer->normalize($obj, $format, $context);
            }
        }

        return array_filter($data, static fn ($val) => null !== $val);
    }

    public function setNormalizer(NormalizerInterface $normalizer): void
    {
        $this->normalizer = $normalizer;
    }

    protected function canListDocument(LsAssociation $obj, string $which): bool
    {
        $target = match ($which) {
            'origin' => $obj->getOrigin(),
            'destination' => $obj->getDestination(),
            default => throw new \InvalidArgumentException('Expected "origin" or "destination"'),
        };

        if (!is_object($target)) {
            return true;
        }

        $targetDoc = match (true) {
            $target instanceof LsDoc => $target,
            $target instanceof LsItem => $target->getLsDoc(),
        };

        if (LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT !== $targetDoc->getAdoptionStatus()) {
            return true;
        }

        if ($obj->getLsDoc()?->getId() === $targetDoc->getId()) {
            // Even if private draft, we can view if the targetDoc is the same as this one
            return true;
        }

        return $this->authorizationChecker->isGranted(Permission::FRAMEWORK_LIST, $targetDoc);
    }
}
