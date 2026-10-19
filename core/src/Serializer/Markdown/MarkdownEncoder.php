<?php

declare(strict_types=1);

namespace App\Serializer\Markdown;

use App\Service\Markdown\MarkdownBuilder;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

final class MarkdownEncoder implements EncoderInterface
{
    #[\Override]
    public function encode(mixed $data, string $format, array $context = []): string
    {
        if (is_string($data)) {
            return $data;
        }

        $frontMatter = is_array($data['front_matter'] ?? null) ? $data['front_matter'] : [];
        $body = is_string($data['body'] ?? null) ? $data['body'] : '';
        $fmBlock = rtrim(MarkdownBuilder::frontMatter($frontMatter));

        return '' === $fmBlock ? $body : $fmBlock."\n\n".$body;
    }

    #[\Override]
    public function supportsEncoding(string $format): bool
    {
        return 'md' === $format;
    }
}
