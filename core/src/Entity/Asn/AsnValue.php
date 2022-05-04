<?php

namespace App\Entity\Asn;

final class AsnValue
{
    public null|string|int|\DateTime $value = null;
    public ?string $type = null;
    public ?string $datatype = null;
    public ?string $lang = null;

    public function __construct()
    {
    }

    public function getValue(): \DateTime|int|string|null
    {
        return $this->value;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getDatatype(): ?string
    {
        return $this->datatype;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public static function fromArray(array $arr): static
    {
        $value = new static();

        if (array_key_exists('datatype', $arr)) {
            $value->datatype = $arr['datatype'];
        }
        if (array_key_exists('type', $arr)) {
            $value->type = $arr['type'];
        }
        if (array_key_exists('value', $arr)) {
            if ('http://www.w3.org/2001/XMLSchema#date' === $value->datatype) {
                $value->value = new \DateTime($arr['value']);
            } else {
                $value->value = $arr['value'];
            }
        }
        if (array_key_exists('lang', $arr)) {
            $value->lang = $arr['lang'];
        }

        return $value;
    }

    public static function fromJson(string $json): static
    {
        $arr = json_decode($json);

        return static::fromArray($arr);
    }
}
