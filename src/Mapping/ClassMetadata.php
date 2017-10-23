<?php

namespace Swisscat\SalesforceBundle\Mapping;

use Swisscat\SalesforceBundle\Mapping\Identification\StrategyInterface;

class ClassMetadata
{
    private $fieldMappings = [];

    private $identifier;

    private $salesforceType;

    private $externalIdMapping = false;

    private $localIdMapping = false;

    /**
     * @var StrategyInterface[]
     */
    private $identificationStrategies = [];

    public function addIdentificationStrategy(StrategyInterface $strategy): void
    {
        $this->identificationStrategies[] = $strategy;
    }

    /**
     * @return StrategyInterface[]
     */
    public function getIdentificationStrategies()
    {
        return $this->identificationStrategies;
    }

    public function getFieldNames()
    {
        return array_keys($this->fieldMappings);
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function setIdentifier(array $identifierData)
    {
        $this->identifier = $identifierData['name'];
        $this->externalIdMapping = ($identifierData['externalId'] ?? 'None') != 'None' ?: false;
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

    public function hasExternalIdMapping()
    {
        return (bool)$this->externalIdMapping;
    }

    public function getExternalIdMapping()
    {
        return $this->externalIdMapping;
    }
}