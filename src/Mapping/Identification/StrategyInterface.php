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
     * @param $entity
     * @param string $salesforceId
     * @param string $action
     */
    public function persistSalesforceAction($entity, string $salesforceId, string $action): void;
}