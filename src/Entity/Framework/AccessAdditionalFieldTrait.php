<?php

namespace App\Entity\Framework;

trait AccessAdditionalFieldTrait
{
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

    public function __get(string $name)
    {
        if (0 === strpos($name, 'custom_')) {
            return $this->getAdditionalField(substr($name, 7));
        }

        throw new \BadMethodCallException(sprintf('The property "%s" does not exist.', $name));
    }

    public function __set(string $name, $value): void
    {
        if (0 === strpos($name, 'custom_')) {
            $this->setAdditionalField(substr($name, 7), $value);

            return;
        }

        throw new \BadMethodCallException(sprintf('The property "%s" does not exist.', $name));
    }

    public function __isset(string $name): bool
    {
        if (0 === strpos($name, 'custom_')) {
            $customFields = $this->getExtraProperty('customFields') ?? [];

            return array_key_exists(substr($name, 7), $customFields);
        }

        throw new \BadMethodCallException(sprintf('The property "%s" does not exist.', $name));
    }

    public function __unset(string $name): void
    {
        if (0 === strpos($name, 'custom_')) {
            $customFields = $this->getExtraProperty('customFields') ?? [];

            unset($customFields[$name]);
            $this->setExtraProperty('customFields', $customFields);

            return;
        }

        throw new \BadMethodCallException(sprintf('The property "%s" does not exist.', $name));
    }

}
