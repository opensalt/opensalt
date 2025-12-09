<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\LsItemRepository;
use App\Service\Api1Uris;
use App\Service\UriGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UriCredentialView extends AbstractController
{
    public function __construct(
        private readonly UriGenerator $uriGenerator,
        private readonly LsAssociationRepository $associationRepository,
        private readonly LsItemRepository $itemRepository,
        private readonly Api1Uris $api1Uris,
    ) {
    }

    public function renderCredentialView(LsItem $obj, Request $request, Response $response): Response
    {
        $response->setPublic();

        $credential = $obj->getExtensionProperty('ob3') ?? ($obj->getExtraProperty('extendedItem') ?? [])['ob3'] ?? 'null';
        if (is_string($credential)) {
            $credential = json5_decode($credential, true);
        }

        if (null === $credential) {
            $credential = [
                'type' => ['Achievement'],
                'achievementType' => null,
                'name' => $obj->getAbbreviatedStatement() ?? $obj->getFullStatement(),
                'description' => $obj->getFullStatement(),
                'humanCode' => $obj->getHumanCodingScheme() ?? '',
                'criteria' => [
                    'narrative' => '',
                    'id' => $this->uriGenerator->getUri($obj).'.html',
                ],
                'alignment' => [],
                'image' => [
                    'id' => '',
                    'type' => 'Image',
                ],
            ];
        }

        $iri = $this->api1Uris->getUri($obj);
        $credential = array_merge(['@context' => [], 'id' => $iri], $credential);

        $img = ('' !== ($credential['image']['id'] ?? '')) ? $credential['image']['id'] : '';

        $allAssociations = $this->associationRepository->findAllAssociationsForAsSplitArray($obj->getIdentifier());
        $associations = $allAssociations['associations'];
        $criteria = [];
        $alignments = [];
        foreach ($associations as $association) {
            $destination = $association->getDestination();

            if ($destination instanceof LsDoc) {
                continue;
            }

            switch ($association->getType()) {
                case LsAssociation::PRECEDES:
                    // case LsAssociation::CHILD_OF:
                    break;

                case LsAssociation::EXEMPLAR:
                    if (is_string($destination) && (str_ends_with($destination, '.png') || str_ends_with($destination, '.svg'))) {
                        $img = $destination;
                    }
                    break;

                default:
                    if ($destination instanceof LsItem) {
                        $alignments[$destination->getIdentifier()] = $destination;
                    }
                    break;
            }
        }

        $credential['image']['id'] = $img;

        $associations = $allAssociations['inverseAssociations'];
        foreach ($associations as $association) {
            $origin = $association->getOrigin();
            if (is_string($origin)) {
                $origin = $this->itemRepository->findOneBy(['identifier' => $association->getOriginNodeIdentifier()]);
            }
            if (!$origin instanceof LsItem) {
                continue;
            }

            switch ($association->getType()) {
                case LsAssociation::EXEMPLAR:
                    // case LsAssociation::CHILD_OF:
                case LsAssociation::EXACT_MATCH_OF:
                    break;

                case LsAssociation::PRECEDES:
                    $criteria[$origin->getIdentifier()] = $origin;
                    break;

                default:
                    $alignments[$origin->getIdentifier()] = $origin;
                    break;
            }
        }

        // If we have an alignment in the criteria, remove it from the alignments
        foreach (array_keys($criteria) as $key) {
            if (array_key_exists($key, $alignments)) {
                unset($alignments[$key]);
            }
        }

        if (null === $credential['achievementType']) {
            $achievementType = preg_replace('/Credential - /', '', $obj->getItemType()?->getTitle() ?? 'Credential - Achievement');
            if (!in_array($achievementType, [
                'Achievement',
                'ApprenticeshipCertificate',
                'Assessment',
                'Assignment',
                'AssociateDegree',
                'Award',
                'Badge',
                'BachelorDegree',
                'Certificate',
                'CertificateOfCompletion',
                'Certification',
                'CommunityService',
                'Competency',
                'Course',
                'CoCurricular',
                'Degree',
                'Diploma',
                'DoctoralDegree',
                'Fieldwork',
                'GeneralEducationDevelopment',
                'JourneymanCertificate',
                'LearningProgram',
                'License',
                'Membership',
                'ProfessionalDoctorate',
                'QualityAssuranceCredential',
                'MasterCertificate',
                'MasterDegree',
                'MicroCredential',
                'ResearchDoctorate',
                'SecondarySchoolDiploma',
            ], true)) {
                $achievementType = 'ext:'.$achievementType;
            }

            $credential['achievementType'] = $achievementType;
        }

        if ('jsonld' === $request->getRequestFormat()) {
            $narrative = [];
            foreach ($criteria as $criterion) {
                $narrative[] = '- '.($criterion->getAbbreviatedStatement() ?? $criterion->getFullStatement());
            }
            if ('' !== ($credential['criteria']['narrative'] ?? '')) {
                $credential['criteria']['narrative'] .= "\n\n";
            }
            $credential['criteria']['narrative'] .= implode("\n", $narrative);

            if ('' === $credential['criteria']['narrative']) {
                unset($credential['criteria']['narrative']);
            }

            foreach ($alignments as $alignment) {
                $credential['alignment'][] = [
                    'type' => 'Alignment',
                    // 'targetCode' => $alignment->getIdentifier(),
                    // 'targetDescription' => $alignment->getFullStatement(),
                    'targetName' => $alignment->getAbbreviatedStatement() ?? $alignment->getFullStatement(),
                    // 'targetFramework' => $alignment->getFramework(),
                    // 'targetType' => $alignment->getItemType()?->getTitle() ?? '',
                    'targetType' => 'CFItem',
                    'targetUrl' => $this->uriGenerator->getUri($alignment),
                ];
            }
            if ([] === ($credential['alignment'] ?? 'X')) {
                unset($credential['alignment']);
            }

            if ('' === ($credential['humanCode'] ?? 'X')) {
                unset($credential['humanCode']);
            }

            if ('' === ($credential['image']['id'] ?? 'X')) {
                unset($credential['image']);
            }

            return new JsonResponse($credential, Response::HTTP_OK);
        }

        return $this->render('uri/credential_view.html.twig', [
            'obj' => $obj,
            'img' => $img,
            'criteria' => $criteria,
            'alignments' => $alignments,
            'associationRepo' => $this->associationRepository,
            'itemRepo' => $this->itemRepository,
            'credential' => $credential,
        ], $response);
    }
}
