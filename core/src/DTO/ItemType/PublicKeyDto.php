<?php

namespace App\DTO\ItemType;

use App\Entity\Framework\LsItem;
use App\Form\Type\ItemType\PublicKeyType;
use App\Form\Validator\ValidPublicKey;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PublicKeyDto implements ItemTypeInterface
{
    public const int ITEM_TYPE_IDENTIFIER = LsItem::TYPES['public_key'];
    public const string ITEM_TYPE_FORM = PublicKeyType::class;
    public const string TYPE_KEY = 'salt:kid';

    public function __construct(
        public ?string $kid = null,
        #[Assert\NotNull()]
        #[Assert\NotBlank()]
        #[Assert\Json(message: 'This must be a key in JWK format.')]
        #[ValidPublicKey()]
        public ?string $publicKey = null,
        public ?string $type = 'jwk',
    ) {
    }

    #[\Override]
    public static function fromItem(LsItem $item): self
    {
        return new self(
            $item->getAbbreviatedStatement(),
            $item->getFullStatement(),
            $item->getExtensionProperty(self::TYPE_KEY)
        );
    }

    #[\Override]
    public function applyToItem(LsItem $item, HtmlSanitizerInterface $htmlSanitizer): void
    {
        /** @var JWK $key */
        $key = JWKFactory::createFromValues(json_decode($this->publicKey, true));
        if (null === ($key->jsonSerialize()['kid'] ?? null)) {
            $key = JWKFactory::createFromValues([...$key->jsonSerialize(), 'kid' => $key->thumbprint('sha256')]);
        }

        $item->setAbbreviatedStatement($key->get('kid'));
        $item->setFullStatement(json_encode($key->jsonSerialize()) ?: null);
        $item->setExtensionProperty(LsItem::TYPE_KEY, 'public_key');
        $item->setExtensionProperty(self::TYPE_KEY, $this->type);
    }
}
