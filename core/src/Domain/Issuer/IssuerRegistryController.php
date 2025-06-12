<?php

declare(strict_types=1);

namespace App\Domain\Issuer;

use App\Repository\Framework\IdentifierItemRepository;
use App\Repository\Framework\LsDefItemTypeRepository;
use App\Repository\Framework\LsItemRepository;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\JWSSerializerManagerFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

#[AsController]
#[Route('/issuer-registry')]
class IssuerRegistryController extends AbstractController
{
    public function __construct(
        #[Autowire(param: 'issuer_signing_key')] private readonly ?string $issuerSigningKey,
       private readonly JWSSerializerManagerFactory $serializerManagerFactory,
    ) {
    }

    #[Route('/', 'issuer_registry_prefix')]
    public function registry_prefix(): Response
    {
        return $this->redirectToRoute('issuer_registry_index');
    }

    #[Route('/.well-known/openid-federation', 'issuer_registry_root')]
    public function index(): Response
    {
        $key = $this->getSigningKey();

        $keySet = new JWKSet([$key->toPublic()]);

        $registry = [
            'sub' => $this->generateUrl('issuer_registry_prefix', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'metadata' => [
                'federation_entity' => [
                    'organization_name' => 'OpenSALT',
                    'homepage_uri' => $this->generateUrl('salt_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    'logo_uri' => 'data:image/svg;base64,'.base64_encode(file_get_contents(__DIR__.'/../../../public/static/img/opensalt.svg')),
                    'policy_uri' => $this->generateUrl('issuer_registry_governance', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    'federation_fetch_endpoint' => $this->generateUrl('issuer_registry_fetch', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    'federation_list_endpoint' => $this->generateUrl('issuer_registry_list', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ],
            ],
            'jwks' => $keySet,
            'iss' => $this->generateUrl('issuer_registry_prefix', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'exp' => new \DateTimeImmutable('now + 1 day')->format('U'),
            'iat' => new \DateTimeImmutable('now')->format('U'),
            'jti' => Uuid::v4()->toBase58(),
        ];

        $output = $this->getSignedStatement($registry, $key);

        return new Response($output, Response::HTTP_OK, ['Content-Type' => 'application/entity-statement+jwt']);
    }

    #[Route('/fetch', 'issuer_registry_fetch')]
    public function fetch(
        #[MapQueryParameter] string $sub,
        IdentifierItemRepository $identifierRepository,
    ): Response {
        // ?sub=<did>
        $issuerInfo = $identifierRepository->findIssuerInfo($sub);

        $keys = [];
        foreach ($issuerInfo['keys'] as $issuerKey) {
            $keys[] = JWKFactory::createFromValues(json_decode($issuerKey->publicKey, true))->toPublic();
        }
        $keySet = new JWKSet($keys);

        $ret = [
            'sub' => $sub,
            'metadata' => [
                'federation_entity' => [
                    'organization_name' => $issuerInfo['org']->name,
                    'homepage_uri' => $issuerInfo['org']->webpage,
                    'logo_uri' => $issuerInfo['org']->logo,
                ],
            ],
            // 'jwks' => $keySet, // Removed from upstream work, assuming the keys can be found from the DID by retrieving the DID doc
            'iss' => $this->generateUrl('issuer_registry_prefix', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'exp' => new \DateTimeImmutable('now + 1 day')->format('U'),
            'iat' => new \DateTimeImmutable('now')->format('U'),
            'jti' => Uuid::v4()->toBase58(),
        ];

        if (null !== $issuerInfo['org']->legalName) {
            $ret['metadata']['institutional_additonal_information']['legal_name'] = $issuerInfo['org']->legalName;
        }

        if (null !== $issuerInfo['org']->ctid) {
            $ret['metadata']['credential_registry_entity']['ctid'] = $issuerInfo['org']->ctid;
            $ret['metadata']['credential_registry_entity']['url'] = 'https://credentialengineregistry.org/resources/'.$issuerInfo['org']->ctid;
        }

        if (null !== $issuerInfo['org']->rorId) {
            $ret['metadata']['ror_entity']['rorid'] = $issuerInfo['org']->rorId;
            $ret['metadata']['ror_entity']['url'] = 'https://ror.org/'.$issuerInfo['org']->rorId;
        }

        $key = $this->getSigningKey();

        $output = $this->getSignedStatement($ret, $key);

        return new Response($output, Response::HTTP_OK, ['Content-Type' => 'application/entity-statement+jwt']);
    }

    #[Route('/subordinate_listing', 'issuer_registry_list')]
    public function list(
        IdentifierItemRepository $identifierRepository,
    ): JsonResponse {
        // Find identifiers restricted to the known "Issuer Registry" frameworks owned by salt_org #2 (PCG)
        $identifiers = $identifierRepository->findIssuerIdentifiers();

        // @TODO: check that the identifiers have an organisation and public keys

        $list = [];
        foreach ($identifiers as $identifier) {
            $list[] = $identifier->getUri();
        }

        return new JsonResponse($list);
    }

    #[Route('/governance', 'issuer_registry_governance')]
    public function governance(
        IdentifierItemRepository $identifierItemRepository,
        LsItemRepository $lsItemRepository,
        LsDefItemTypeRepository $itemTypeRepository,
    ): Response {
        $issuerFrameworks = $identifierItemRepository->findIssuerFrameworks();
        $frameworkIds = array_map(fn ($lsDoc): ?int => $lsDoc->getId(), $issuerFrameworks);

        $governanceDocType = $itemTypeRepository->findBy(['title' => 'Governance Document']);

        $governanceDoc = $lsItemRepository->findBy(['lsDoc' => $frameworkIds, 'itemType' => $governanceDocType], null, 1);
        if (count($governanceDoc) < 1) {
            throw $this->createNotFoundException('Governance document not found');
        }

        return $this->render('issuer_registry/governance.html.twig', [
            'governanceDoc' => $governanceDoc[0],
        ]);
    }

    protected function getSigningKey(): JWK
    {
        if (null === $this->issuerSigningKey || '' === $this->issuerSigningKey) {
            $key = JWKFactory::createECKey('P-256', ['kid' => 'placeholder', 'alg' => 'ES256']);
            $key = JWKFactory::createFromValues([...$key->jsonSerialize(), 'kid' => $key->thumbprint('sha256')]);
        } else {
            $key = JWKFactory::createFromValues(json5_decode($this->issuerSigningKey, true));
        }

        if (!$key instanceof JWK) {
            throw new \RuntimeException('Invalid signing key');
        }

        return $key;
    }

    protected function getSignedStatement(array $payload, JWK $key): string
    {
        $jws = new JWSBuilder(new AlgorithmManager([new ES256()]))
            ->create()
            ->withPayload(json_encode($payload))
            ->addSignature($key, ['alg' => $key->get('alg'), 'kid' => $key->get('kid'), 'typ' => 'entity-statement+jwt'])
            ->build();

        $serializer = $this->serializerManagerFactory->create(['jws_compact']);

        return $serializer->serialize('jws_compact', $jws);
    }
}
