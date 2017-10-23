<?php

namespace Swisscat\SalesforceBundle\Mapping\Identification;

interface StrategyInterface
{
    /**
     * @param $entity
     * @return null|string
     */
    public function getSalesforceId($entity): ?string;

    /**
     * @param string $salesforceId
     * @param string $entityClass
     * @return mixed
     */
    public function getEntityBySalesforceId(string $salesforceId, string $entityClass);

    /**
     * @param string $localId
     * @param string $localType
     * @param string $salesforceId
     * @param string $action
     */
    public function persistSalesforceAction(string $localId, string $localType, string $salesforceId, string $action): void;
}