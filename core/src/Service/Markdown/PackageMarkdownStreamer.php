<?php

declare(strict_types=1);

namespace App\Service\Markdown;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsDocRepository;
use App\Serializer\Markdown\MarkdownEncoder;
use App\Serializer\Markdown\PackageMarkdownNormalizer;
use App\Service\UriGenerator;
use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class PackageMarkdownStreamer
{
    private const int BATCH_SIZE = 2000;

    public function __construct(
        private LsDocRepository $docRepository,
        private PackageTreeBuilder $treeBuilder,
        private PackageMarkdownNormalizer $packageNormalizer,
        private NormalizerInterface $normalizer,
        private MarkdownEncoder $encoder,
        private UriGenerator $uriGenerator,
    ) {
    }

    public function stream(LsDoc $doc, Response $template): StreamedResponse
    {
        $headers = [];
        foreach ($template->headers->getIterator() as $key => $value) {
            /** @psalm-var array<array-key, string>|string $value */
            $headers[$key] = $value;
        }

        $tocRows = $this->docRepository->findItemTocRows($doc);
        $parentMap = $this->docRepository->findItemParentMap($doc);
        $ordered = $this->treeBuilder->order($tocRows, $parentMap);

        $tocLines = array_map(fn (array $entry): string => $this->tocLine($entry), $ordered);

        $crosswalks = $this->docRepository->findCrosswalkSummary($doc);
        $header = $this->packageNormalizer->header(
            $doc,
            count($ordered),
            $tocLines,
            $this->countBy($tocRows, 'kind'),
            $this->countBy($tocRows, 'itemType'),
            $crosswalks
        );

        $byNumber = array_column($ordered, null, 'identifier');
        $titleById = [];
        foreach ($ordered as $entry) {
            $titleById[$entry['identifier']] = $entry['title'];
        }

        return new StreamedResponse(function () use ($doc, $byNumber, $header, $parentMap, $titleById): void {
            echo $this->encoder->encode($header, 'md')."\n\n";

            foreach ($this->streamItems($doc) as $item) {
                $entry = $byNumber[$item->getIdentifier() ?? ''] ?? null;
                if (null === $entry) {
                    continue;
                }

                $context = [
                    'markdown-package' => true,
                    'markdown-heading-offset' => 2,
                    'markdown-heading' => [
                        'level' => 3,
                        'text' => $this->sectionHeadingText($entry),
                    ],
                    'markdown-breadcrumb' => $this->breadcrumb($entry, $parentMap, $titleById),
                ];
                /** @var array{front_matter: array, body: string} $model */
                $model = $this->normalizer->normalize($item, 'md', $context);
                // The package supplies the item headings; per-item front matter
                // is redundant inside the package document
                $model['front_matter'] = [];
                echo $this->encoder->encode($model, 'md')."\n\n";
            }

            echo $this->packageNormalizer->tailSections($doc);
        }, $template->getStatusCode(), $headers);
    }

    /**
     * @param array{identifier: string, number: string, depth: int, title: string, code: ?string} $entry
     */
    private function sectionHeadingText(array $entry): string
    {
        $text = $entry['number'].' '.$entry['title'];
        if (null !== $entry['code'] && '' !== $entry['code'] && $entry['code'] !== $entry['title']) {
            $text .= ' ('.$entry['code'].')';
        }

        return $text;
    }

    /**
     * TOC entries link to the item's canonical URI (any representation is
     * available there through content negotiation) and carry the item's kind.
     *
     * @param array{identifier: string, number: string, depth: int, title: string, code: ?string} $entry
     */
    private function tocLine(array $entry): string
    {
        $prefix = str_repeat('  ', $entry['depth']).'- ';
        $uri = $this->uriGenerator->getPublicUriForIdentifier($entry['identifier']);
        $kind = $entry['kind'] ?? 'item';

        if (null === $uri || str_starts_with($uri, 'data:')) {
            return $prefix.$this->sectionHeadingText($entry).' — '.$kind;
        }

        return $prefix.MarkdownBuilder::link($this->sectionHeadingText($entry), $uri).' — '.$kind;
    }

    /**
     * Ancestor titles for the chunk-surviving breadcrumb, walked from the
     * lightweight parent map (streamed items are detached from the ORM).
     *
     * @param array{identifier: string, number: string, depth: int, title: string, code: ?string} $entry
     * @param array<string, ?string> $parentMap
     * @param array<string, string> $titleById
     */
    private function breadcrumb(array $entry, array $parentMap, array $titleById): string
    {
        $crumbs = [];
        $cursor = $entry['identifier'];
        $guard = 0;
        while ($guard++ < 50) {
            $parent = $parentMap[$cursor] ?? null;
            if (null === $parent || !isset($titleById[$parent])) {
                break;
            }
            $crumbs[] = $titleById[$parent];
            $cursor = $parent;
        }
        if ([] === $crumbs) {
            return '';
        }

        $crumbs = array_reverse($crumbs);
        $crumbs[] = $entry['title'];

        return implode(' > ', $crumbs);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<string, int>
     */
    private function countBy(array $rows, string $key): array
    {
        $counts = [];
        foreach ($rows as $row) {
            $value = $row[$key] ?? null;
            if (null === $value || '' === $value) {
                continue;
            }
            $counts[(string) $value] = ($counts[(string) $value] ?? 0) + 1;
        }
        ksort($counts);

        return $counts;
    }

    /**
     * @return \Generator<LsItem>
     */
    private function streamItems(LsDoc $doc): \Generator
    {
        $start = 0;
        $cnt = -1;
        while (0 !== $cnt) {
            $cnt = 0;
            $last = 0;
            $items = $this->docRepository->findAllItemsForCFPackage($doc, Query::HYDRATE_OBJECT, $start, self::BATCH_SIZE);
            $buffer = [];
            foreach ($items as $item) {
                ++$cnt;
                $last = $item->getId();
                $this->docRepository->getEntityManager()->detach($item);
                $buffer[$item->getIdentifier() ?? ''] = $item;
            }
            yield from array_values($buffer);
            unset($items, $buffer);
            $start = $last;
        }
    }
}
