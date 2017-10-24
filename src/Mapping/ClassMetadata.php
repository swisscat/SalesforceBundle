<?php

namespace Swisscat\SalesforceBundle\Mapping;

use Swisscat\SalesforceBundle\Mapping\Identification\StrategyInterface;

class ClassMetadata
{
    private $fieldMappings = [];

    private $salesforceType;

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