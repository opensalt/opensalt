<?php

namespace App\Entity\Asn;

class AsnValue
{
    /**
     * @var string|int|\DateTime
     */
    public $value;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $datatype;

    /**
     * @var string
     */
    public $lang;


    public function __construct()
    {
    }

    /**
     * @return \DateTime|int|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getDatatype()
    {
        return $this->datatype;
    }

    /**
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    public static function fromArray($arr)
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

    public static function fromJson($json)
    {
        $arr = json_decode($json);

        return static::fromArray($arr);
    }
}
