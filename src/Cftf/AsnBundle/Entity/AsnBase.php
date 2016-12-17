<?php
/**
 *
 */

namespace Cftf\AsnBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class AsnBase
 * @package Cftf\AsnBundle\Entity
 */
abstract class AsnBase
{
    /**
     * @ var array<string, ArrayCollection<AsnValue>>
     * @var ArrayCollection[]
     */
    public $property = [];

    /**
     * @var array
     */
    public static $properties = [];


    public function __construct()
    {
    }

    public function __call($name, $args)
    {
        if (0 === strpos($name, 'get')) {
            $prop = lcfirst(preg_replace('/^get/', '', $name));
            if (array_key_exists($prop, static::$properties)) {
                return $this->property[$prop];
            } else {
                throw new \BadMethodCallException("{$name} does not exist");
            }
        }

        if (0 === strpos($name, 'set')) {
            $prop = lcfirst(preg_replace('/^set/', '', $name));
            if (array_key_exists($prop, static::$properties)) {
                if (1 !== count($args)) {
                    throw new \BadMethodCallException("{$name} requires an argument");
                }
                $this->property[$prop] = $args[0];
                return $this;
            } else {
                throw new \BadMethodCallException("{$name} does not exist");
            }
        }

        throw new \BadMethodCallException("{$name} does not exist");
    }

    public function __get($key)
    {
        if (array_key_exists($key, static::$properties)) {
            if (array_key_exists($key, $this->property)) {
                return $this->property[$key];
            } else {
                return null;
            }
        }

        return null;
    }

    public function __set($key, $value)
    {
        if (array_key_exists($key, static::$properties)) {
            $this->property[$key] = $value;
        }
    }

    /**
     * @param array $arr
     * @return AsnBase
     */
    public static function fromArray($arr)
    {
        $md = new static();

        foreach (static::$properties as $prop=>$key) {
            $md->property[$prop] = $md->arrayValuesToValueCollection($key, $arr);
        }

        return $md;
    }

    /**
     * @param string $json
     * @return AsnBase
     */
    public static function fromJson($json)
    {
        $arr = json_decode($json);
        $md = static::fromArray($arr);

        return $md;
    }

    protected function arrayValuesToValueCollection($key, $values)
    {
        if (array_key_exists($key, $values)) {
            $arr = $values[$key];

            $collection = [];
            foreach ($arr as $value) {
                $collection[] = AsnValue::fromArray($value);
            }

            $arrayCollection = new ArrayCollection($collection);

            return $arrayCollection;
        }

        return null;
    }
}