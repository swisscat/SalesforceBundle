<?php

namespace Swisscat\SalesforceBundle\Mapping\Identification;

class FullRemoteStrategy implements StrategyInterface
{
    /**
     * @var string
     */
    private $matchingField;

    /**
     * @param string $matchingField
     */
    public function setMatchingField(string $matchingField)
    {
        $this->matchingField = $matchingField;
    }

    /**
     * @inheritdoc
     */
    public function getSalesforceId($entity): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getEntityBySalesforceId(string $salesforceId, string $entityClass)
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function persistSalesforceAction(string $localId, string $localType, string $salesforceId, string $action): void
    {
    }

    /**
     * @inheritdoc
     */
    public function getUpsertFieldName(): ?string
    {
        return $this->matchingField;
    }
}