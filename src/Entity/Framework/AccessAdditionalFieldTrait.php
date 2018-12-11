<?php

namespace App\Entity\Framework;

trait AccessAdditionalFieldTrait
{
    abstract public function getExtra(): array;
    abstract public function getExtraProperty(string $property);
    abstract public function setExtraProperty(string $property, $value);

    protected function getAdditionalField(string $name)
    {
        $extra = $this->getExtra();

        return $extra['customFields'][$name] ?? null;
    }

    protected function setAdditionalField(string $name, $value): void
    {
        $customFields = $this->getExtraProperty('customFields') ?? [];

        $customFields[$name] = $value;
        $this->setExtraProperty('customFields', $customFields);
    }

    public function __get($name)
    {
        if (0 === strpos($name, 'custom_')) {
            return $this->getAdditionalField(substr($name, 7));
        }

        throw new \BadMethodCallException(sprintf('The property "%s" does not exist.', $name));
    }

    public function __set($name, $value)
    {
        if (0 === strpos($name, 'custom_')) {
            $this->setAdditionalField(substr($name, 7), $value);

            return;
        }

        throw new \BadMethodCallException(sprintf('The property "%s" does not exist.', $name));
    }

    public function __isset($name)
    {
        if (0 === strpos($name, 'custom_')) {
            $customFields = $this->getExtraProperty('customFields') ?? [];

            return null !== $customFields[substr($name, 7)] ?? null;
        }

        return false;
    }

    public function __unset($name)
    {
        if (0 === strpos($name, 'custom_')) {
            $customFields = $this->getExtraProperty('customFields') ?? [];

            unset($customFields[$name]);
            $this->setExtraProperty('customFields', $customFields);

            return;
        }
    }
}
