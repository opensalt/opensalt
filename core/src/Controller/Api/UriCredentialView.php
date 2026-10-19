<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\LsItemRepository;
use App\Service\Api1Uris;
use App\Service\Markdown\CredentialAssociationsResolver;
use App\Service\UriGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UriCredentialView extends AbstractController
{
    public function __construct(
        private readonly UriGenerator $uriGenerator,
        private readonly CredentialAssociationsResolver $credentialAssociationsResolver,
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

        if (!is_array($credential)) {
            $credential = [];
        }

        // Fill in the keys the template and jsonld output always expect;
        // decoded ob3 values win over the defaults.
        $credential = array_merge([
            'type' => ['Achievement'],
            'achievementType' => null,
            'name' => $obj->getAbbreviatedStatement() ?? $obj->getFullStatement(),
            'description' => $obj->getFullStatement(),
            'humanCode' => $obj->getHumanCodingScheme() ?? '',
            'alignment' => [],
            'image' => ['id' => '', 'type' => 'Image'],
        ], $credential);

        $criteria = is_array($credential['criteria'] ?? null) ? $credential['criteria'] : [];
        $criteria += [
            'narrative' => '',
            'id' => $this->uriGenerator->getUri($obj).'.html',
        ];
        $credential['criteria'] = $criteria;

        $iri = $this->api1Uris->getUri($obj);
        $credential = array_merge(['@context' => [], 'id' => $iri], $credential);

        if (!is_array($credential['image'] ?? null)) {
            $credential['image'] = ['id' => '', 'type' => 'Image'];
        }

        $img = ('' !== ($credential['image']['id'] ?? '')) ? $credential['image']['id'] : '';

        $resolved = $this->credentialAssociationsResolver->resolve($obj);
        $criteria = $resolved['criteria'];
        $alignments = $resolved['alignments'];

        $images = $this->credentialAssociationsResolver->getExemplarImages($obj);
        if ('' === $img && [] !== $images) {
            $img = $images[0];
        }

        $credential['image']['id'] = $img;

        if (null === ($credential['achievementType'] ?? null)) {
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

            $combinedNarrative = (string) ($credential['criteria']['narrative'] ?? '');
            if ('' !== $combinedNarrative) {
                $combinedNarrative .= "\n\n";
            }
            $combinedNarrative .= implode("\n", $narrative);

            if ('' !== $combinedNarrative) {
                $credential['criteria']['narrative'] = $combinedNarrative;
            } else {
                unset($credential['criteria']['narrative']);
            }

            if (!is_array($credential['alignment'] ?? null)) {
                $credential['alignment'] = [];
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
