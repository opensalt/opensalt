<?php

declare(strict_types=1);

namespace App\DTO\ItemType;

use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use App\Form\Type\ItemType\CredentialType;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CredentialDto implements ItemTypeInterface
{
    public const int ITEM_TYPE_IDENTIFIER = LsItemKind::Credential->value;
    public const string ITEM_TYPE_FORM = CredentialType::class;
    public const string CREDENTIAL_KEY = 'ob3';

    public function __construct(
        #[Assert\NotBlank()]
        public ?string $credential = null,
    ) {
    }

    #[\Override]
    public static function fromItem(LsItem $item): self
    {
        $ob3 = $item->getExtensionProperty(self::CREDENTIAL_KEY);

        if (is_array($ob3)) {
            // The ob3 is stored as an array instead of a string
            $ob3 = json_encode($ob3, JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
        }

        return new self($ob3);
    }

    #[\Override]
    public function applyToItem(LsItem $item, HtmlSanitizerInterface $htmlSanitizer): void
    {
        $credentialInfo = $this->decodeCredentialInfo();
        $description = $credentialInfo['description'] ?? null;
        if (null !== $description) {
            $credentialInfo['description'] = $htmlSanitizer->sanitizeFor('div', $description);
        }
        $narrative = $credentialInfo['criteria']['narrative'] ?? null;
        if (null !== $narrative) {
            $credentialInfo['criteria']['narrative'] = $htmlSanitizer->sanitizeFor('div', $narrative);
        }

        $item->setAbbreviatedStatement($credentialInfo['name'] ?? null);
        $item->setFullStatement($credentialInfo['description'] ?? '');
        $item->setHumanCodingScheme($credentialInfo['humanCode'] ?? null);
        $item->setLanguage($credentialInfo['inLanguage'] ?? null);
        $item->setConceptKeywordsArray($credentialInfo['tag'] ?? null);
        $item->setExtensionProperty(LsItem::TYPE_KEY, 'credential');
        $item->setExtensionProperty(self::CREDENTIAL_KEY, json_encode($credentialInfo, JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES));
    }

    /**
     * Decode the credential JSON into an array, tolerating double-encoded
     * (pre-stringified) values and malformed input without throwing.
     *
     * @return array<string, mixed>
     */
    private function decodeCredentialInfo(): array
    {
        if (null === $this->credential || '' === $this->credential) {
            return [];
        }

        try {
            $decoded = json5_decode($this->credential, true);
            if (is_string($decoded)) {
                $decoded = json5_decode($decoded, true);
            }
        } catch (\Throwable) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }
}
