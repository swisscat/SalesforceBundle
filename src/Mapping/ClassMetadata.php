<?php

namespace Swisscat\SalesforceBundle\Mapping;

class ClassMetadata
{
    private $fieldMappings;

    private $identifier;

    private $salesforceType;

    public function getFieldNames()
    {
        return array_keys($this->fieldMappings);
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    public function getSalesforceType()
    {
        return $this->salesforceType;
    }

    public function setSalesforceType(string $saleforceType)
    {
        $this->salesforceType = $saleforceType;
    }

    public function setFieldMapping(string $fieldName, array $mapping)
    {
        $this->fieldMappings[$fieldName] = $mapping;
    }

    public function getFieldMapping(string $fieldName)
    {
        if (!isset($this->fieldMappings[$fieldName])) {
            throw new \InvalidArgumentException('fieldNameNotMapped');
        }

        return $this->fieldMappings[$fieldName];
    }
}