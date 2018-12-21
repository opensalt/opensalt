<?php

namespace App\Entity\Framework;

trait AccessAdditionalFieldTrait
{
    abstract public function getExtraProperty(string $property);
    abstract public function setExtraProperty(string $property, $value);

    public function getAdditionalFields(): array
    {
        return $this->getExtraProperty('customFields') ?? [];
    }

    public function setAdditionalFields(array $values): void
    {
        foreach ($values as $key => $value) {
            if (null === $value) {
                unset($values[$key]);
            }
        }

        if ([] === $values) {
            $this->setExtraProperty('customFields', null);

            return;
        }

        $this->setExtraProperty('customFields', $values);
    }

    public function getAdditionalField(string $name)
    {
        $customFields = $this->getExtraProperty('customFields') ?? [];

        return $customFields[$name] ?? null;
    }

    public function setAdditionalField(string $name, $value): void
    {
        $customFields = $this->getExtraProperty('customFields') ?? [];

        $customFields[$name] = $value;
        if (null === $value) {
            unset($customFields[$name]);
        }

        if ([] === $customFields) {
            $this->setExtraProperty('customFields', null);

            return;
        }

        $this->setExtraProperty('customFields', $customFields);
    }
}
