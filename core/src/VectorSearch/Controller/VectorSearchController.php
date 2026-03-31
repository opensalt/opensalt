<?php

declare(strict_types=1);

namespace App\VectorSearch\Controller;

use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use App\VectorSearch\Entity\LsItemEmbedding;
use App\VectorSearch\Service\VectorSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class VectorSearchController extends AbstractController
{
    public function __construct(
        private readonly VectorSearchService $vectorSearchService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/vector-search', name: 'admin_vector_search')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): Response
    {
        return $this->render('vector_search/index.html.twig', $this->buildNavigationContext() + [
            'vectorCount' => $this->vectorSearchService->getVectorCount(),
        ]);
    }

    #[Route('/admin/vector-search/near-text', name: 'admin_vector_search_near_text')]
    #[IsGranted('ROLE_ADMIN')]
    public function nearText(Request $request): Response
    {
        $form = $this->createFormBuilder(
            [
                'query' => '',
                'framework' => null,
                'leafOnly' => false,
                'limit' => 10,
                'kind' => null,
            ],
            [
                'method' => 'GET',
                'csrf_protection' => false,
            ]
        )
            ->add('query', TextType::class, [
                'label' => 'Text to base search on',
            ])
            ->add('framework', ChoiceType::class, [
                'label' => 'Framework to search',
                'required' => false,
                'placeholder' => 'All frameworks',
                'choices' => $this->getFrameworkChoices(),
                'choice_translation_domain' => false,
            ])
            ->add('leafOnly', CheckboxType::class, [
                'label' => 'Only search leaf nodes',
                'required' => false,
            ])
            ->add('kind', ChoiceType::class, [
                'label' => 'Item kind',
                'required' => false,
                'placeholder' => 'All kinds',
                'choices' => $this->getKindChoices(),
                'choice_translation_domain' => false,
            ])
            ->add('limit', IntegerType::class, [
                'label' => 'Result limit',
                'attr' => ['min' => 1, 'max' => 100],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Search near text',
            ])
            ->getForm();

        $form->handleRequest($request);

        $results = [];
        $currentFilters = [
            'frameworkId' => null,
            'leafOnly' => false,
            'kind' => null,
        ];

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{query: string, framework: int|string|null, leafOnly: bool, limit: int, kind: int|string|null} $data */
            $data = $form->getData();
            $currentFilters = [
                'frameworkId' => $this->normalizeFrameworkId($data['framework']),
                'leafOnly' => (bool) $data['leafOnly'],
                'kind' => $this->normalizeKind($data['kind']),
            ];

            if ('' !== $data['query']) {
                $results = $this->vectorSearchService->searchByQuery(
                    $data['query'],
                    $data['limit'],
                    $currentFilters['frameworkId'],
                    $currentFilters['leafOnly'],
                    $currentFilters['kind']
                );
            }
        }

        return $this->render('vector_search/form.html.twig', $this->buildNavigationContext() + [
            'title' => 'Near Text Search',
            'form' => $form->createView(),
            'results' => $results,
            'mode' => 'near_text',
            'vectorCount' => $this->vectorSearchService->getVectorCount(),
            'currentFilters' => $currentFilters,
            'kinds' => LsItemKind::TYPES,
        ]);
    }

    #[Route('/admin/vector-search/keyword', name: 'admin_vector_search_keyword')]
    #[IsGranted('ROLE_ADMIN')]
    public function keyword(Request $request): Response
    {
        $form = $this->createFormBuilder(
            [
                'query' => '',
                'framework' => null,
                'leafOnly' => false,
                'limit' => 25,
                'kind' => null,
            ],
            [
                'method' => 'GET',
                'csrf_protection' => false,
            ]
        )
            ->add('query', TextType::class, [
                'label' => 'Keyword or phrase',
            ])
            ->add('framework', ChoiceType::class, [
                'label' => 'Framework to search',
                'required' => false,
                'placeholder' => 'All frameworks',
                'choices' => $this->getFrameworkChoices(),
                'choice_translation_domain' => false,
            ])
            ->add('leafOnly', CheckboxType::class, [
                'label' => 'Only search leaf nodes',
                'required' => false,
            ])
            ->add('kind', ChoiceType::class, [
                'label' => 'Item kind',
                'required' => false,
                'placeholder' => 'All kinds',
                'choices' => $this->getKindChoices(),
                'choice_translation_domain' => false,
            ])
            ->add('limit', IntegerType::class, [
                'label' => 'Result limit',
                'attr' => ['min' => 1, 'max' => 100],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Search by keyword',
            ])
            ->getForm();

        $form->handleRequest($request);

        $results = [];
        $currentFilters = [
            'frameworkId' => null,
            'leafOnly' => false,
            'kind' => null,
        ];

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{query: string, framework: int|string|null, leafOnly: bool, limit: int, kind: int|string|null} $data */
            $data = $form->getData();
            $currentFilters = [
                'frameworkId' => $this->normalizeFrameworkId($data['framework']),
                'leafOnly' => (bool) $data['leafOnly'],
                'kind' => $this->normalizeKind($data['kind']),
            ];

            if ('' !== $data['query']) {
                $results = $this->vectorSearchService->searchByKeyword(
                    $data['query'],
                    $data['limit'],
                    $currentFilters['frameworkId'],
                    $currentFilters['leafOnly'],
                    $currentFilters['kind']
                );
            }
        }

        return $this->render('vector_search/form.html.twig', $this->buildNavigationContext() + [
            'title' => 'Keyword Search',
            'form' => $form->createView(),
            'results' => $results,
            'mode' => 'keyword',
            'vectorCount' => $this->vectorSearchService->getVectorCount(),
            'currentFilters' => $currentFilters,
            'kinds' => LsItemKind::TYPES,
        ]);
    }

    #[Route('/admin/vector-search/near-object', name: 'admin_vector_search_near_object')]
    #[Route('/admin/vector-search/near-object/{itemId}', name: 'admin_vector_search_near_object_item', requirements: ['itemId' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function nearObject(Request $request, ?int $itemId = null): Response
    {
        $form = $this->createFormBuilder(
            [
                'itemId' => $itemId,
                'framework' => null,
                'leafOnly' => false,
                'limit' => 10,
                'kind' => null,
            ],
            [
                'method' => 'GET',
                'csrf_protection' => false,
            ]
        )
            ->add('itemId', IntegerType::class, [
                'label' => 'Source item ID',
                'required' => false,
                'attr' => ['min' => 1],
            ])
            ->add('framework', ChoiceType::class, [
                'label' => 'Framework to search',
                'required' => false,
                'placeholder' => 'All frameworks',
                'choices' => $this->getFrameworkChoices(),
                'choice_translation_domain' => false,
            ])
            ->add('leafOnly', CheckboxType::class, [
                'label' => 'Only search leaf nodes',
                'required' => false,
            ])
            ->add('kind', ChoiceType::class, [
                'label' => 'Item kind',
                'required' => false,
                'placeholder' => 'All kinds',
                'choices' => $this->getKindChoices(),
                'choice_translation_domain' => false,
            ])
            ->add('limit', IntegerType::class, [
                'label' => 'Result limit',
                'attr' => ['min' => 1, 'max' => 100],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Search near item',
            ])
            ->getForm();

        $form->handleRequest($request);

        $results = [];
        $sourceItem = null;
        $warning = null;
        $currentFilters = [
            'frameworkId' => null,
            'leafOnly' => false,
            'kind' => null,
        ];

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{itemId: int|null, framework: int|string|null, leafOnly: bool, limit: int, kind: int|string|null} $data */
            $data = $form->getData();
            $currentFilters = [
                'frameworkId' => $this->normalizeFrameworkId($data['framework']),
                'leafOnly' => (bool) $data['leafOnly'],
                'kind' => $this->normalizeKind($data['kind']),
            ];

            if (null !== $data['itemId']) {
                $sourceItem = $this->entityManager->find(LsItem::class, $data['itemId']);

                if (!$sourceItem instanceof LsItem) {
                    $warning = sprintf('Item %d was not found.', $data['itemId']);
                } elseif (!$this->vectorSearchService->hasEmbedding($sourceItem)) {
                    $warning = sprintf('Item %d does not have an embedding yet.', $data['itemId']);
                } else {
                    $results = $this->vectorSearchService->searchByLsItem(
                        $sourceItem,
                        $data['limit'],
                        $currentFilters['frameworkId'],
                        $currentFilters['leafOnly'],
                        $currentFilters['kind']
                    );
                }
            }
        }

        return $this->render('vector_search/form.html.twig', $this->buildNavigationContext() + [
            'title' => 'Near Object Search',
            'form' => $form->createView(),
            'results' => $results,
            'mode' => 'near_object',
            'sourceItem' => $sourceItem,
            'warning' => $warning,
            'vectorCount' => $this->vectorSearchService->getVectorCount(),
            'currentFilters' => $currentFilters,
            'kinds' => LsItemKind::TYPES,
        ]);
    }

    #[Route('/admin/vector-search/item/{id}', name: 'admin_vector_search_detail', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function detail(int $id): Response
    {
        $lsItem = $this->entityManager->find(LsItem::class, $id);
        if (!$lsItem instanceof LsItem) {
            throw $this->createNotFoundException(sprintf('LsItem %d was not found.', $id));
        }

        $embedding = $this->vectorSearchService->getEmbedding($lsItem);

        return $this->render('vector_search/detail.html.twig', $this->buildNavigationContext() + [
            'item' => $lsItem,
            'embedding' => $embedding,
            'itemData' => $this->buildItemDetailData($lsItem, $embedding),
            'vectorCount' => $this->vectorSearchService->getVectorCount(),
            'kinds' => LsItemKind::TYPES,
        ]);
    }

    #[Route('/admin/vector-search/stats', name: 'admin_vector_search_stats')]
    #[IsGranted('ROLE_ADMIN')]
    public function stats(): Response
    {
        $vectorCount = $this->vectorSearchService->getVectorCount();

        return $this->json([
            'vector_count' => $vectorCount,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function buildNavigationContext(): array
    {
        return [
            'indexUrl' => $this->generateUrl('admin_vector_search'),
            'nearTextUrl' => $this->generateUrl('admin_vector_search_near_text'),
            'keywordUrl' => $this->generateUrl('admin_vector_search_keyword'),
            'nearObjectUrl' => $this->generateUrl('admin_vector_search_near_object'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildItemDetailData(LsItem $lsItem, ?LsItemEmbedding $embedding): array
    {
        return [
            'itemId' => $lsItem->getId(),
            'identifier' => $lsItem->getIdentifier(),
            'uri' => $lsItem->getUri(),
            'framework' => $lsItem->getLsDoc()->getTitle(),
            'frameworkIdentifier' => $lsItem->getLsDocIdentifier(),
            'humanCodingScheme' => $lsItem->getHumanCodingScheme(),
            'abbreviatedStatement' => $lsItem->getAbbreviatedStatement(),
            'fullStatement' => $lsItem->getFullStatement(),
            'embedding' => [
                'exists' => null !== $embedding,
                'embeddingId' => $embedding?->getId(),
                'isLeafNode' => $embedding?->isLeafNode(),
                'sourceHierarchyUpdatedAt' => $embedding?->getSourceHierarchyUpdatedAt()?->format('Y-m-d H:i:s'),
                'createdAt' => $embedding?->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $embedding?->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'text' => $embedding?->getText(),
            ],
        ];
    }

    /**
     * @return array<string, array<string, int>>
     */
    private function getFrameworkChoices(): array
    {
        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
        $rows = $queryBuilder
            ->select('d.id', 'd.title', 'd.creator')
            ->from('ls_doc', 'd')
            ->orderBy('d.creator', 'ASC')
            ->addOrderBy('d.title', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $choices = [];
        foreach ($rows as $row) {
            $creator = trim((string) ($row['creator'] ?? ''));
            $title = trim((string) ($row['title'] ?? ''));
            $group = '' !== $creator ? $creator : 'Unknown creator';
            $label = '' !== $title ? $title : sprintf('Framework #%d', (int) $row['id']);
            $choices[$group][$label] = (int) $row['id'];
        }

        return $choices;
    }

    private function normalizeFrameworkId(mixed $frameworkId): ?int
    {
        if (null === $frameworkId || '' === $frameworkId) {
            return null;
        }

        return (int) $frameworkId;
    }

    private function getKindChoices(): array
    {
        $choices = [];
        foreach (LsItemKind::cases() as $case) {
            $label = ucfirst(str_replace('_', ' ', $case->name));
            $choices[$label] = $case->value;
        }

        return $choices;
    }

    private function normalizeKind(mixed $kind): ?int
    {
        if (null === $kind || '' === $kind) {
            return null;
        }

        $value = (int) $kind;

        return LsItemKind::tryFrom($value)?->value;
    }
}
