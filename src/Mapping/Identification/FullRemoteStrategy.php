<?php

namespace Swisscat\SalesforceBundle\Mapping\Identification;

class FullRemoteStrategy implements StrategyInterface
{
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
    public function persistSalesforceAction($entity, string $salesforceId, string $action): void
    {
    }
}