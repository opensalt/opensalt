<?php

namespace App\Entity\Asn;

use Doctrine\Common\Collections\ArrayCollection;

abstract class AsnBase
{
    /**
     * @var array<array-key, ArrayCollection<array-key, AsnValue>>
     */
    public array $property = [];

    public static array $properties = [];

    final public function __construct()
    {
    }

    public function __call(string $name, mixed $args): mixed
    {
        if (str_starts_with($name, 'get')) {
            $prop = lcfirst(preg_replace('/^get/', '', $name));
            if (array_key_exists($prop, static::$properties)) {
                return $this->property[$prop];
            }

            throw new \BadMethodCallException("{$name} does not exist");
        }

        if (str_starts_with($name, 'set')) {
            $prop = lcfirst(preg_replace('/^set/', '', $name));
            if (array_key_exists($prop, static::$properties)) {
                if (1 !== (is_countable($args) ? count($args) : 0)) {
                    throw new \BadMethodCallException("{$name} requires an argument");
                }
                $this->property[$prop] = $args[0];

                return $this;
            }

            throw new \BadMethodCallException("{$name} does not exist");
        }

        throw new \BadMethodCallException("{$name} does not exist");
    }

    public function __get(string $key): mixed
    {
        if (array_key_exists($key, static::$properties)) {
            if (array_key_exists($key, $this->property)) {
                return $this->property[$key];
            }

            return null;
        }

        return null;
    }

    public function __set(string $key, mixed $value): void
    {
        if (array_key_exists($key, static::$properties)) {
            $this->property[$key] = $value;
        }
    }

    public function __isset(string $key): bool
    {
        return isset($this->property[$key]);
    }

    public static function fromArray(array $arr): static
    {
        $md = new static();

        foreach (static::$properties as $prop=>$key) {
            $md->property[$prop] = $md->arrayValuesToValueCollection($key, $arr);
        }

        return $md;
    }

    public static function fromJson(string $json): static
    {
        $arr = json_decode($json);

        return static::fromArray($arr);
    }

    protected function arrayValuesToValueCollection(string $key, array $values): ?ArrayCollection
    {
        if (array_key_exists($key, $values)) {
            $arr = $values[$key];

            $collection = [];
            foreach ($arr as $value) {
                $collection[] = AsnValue::fromArray($value);
            }

            return new ArrayCollection($collection);
        }

        return null;
    }
}
