<?php

namespace Swisscat\SalesforceBundle\Mapping\Identification;

use Swisscat\SalesforceBundle\Mapping\Action;

class PropertyStrategy implements StrategyInterface
{
    use EntityManagedTrait;

    private $property;

    public function setProperty(string $property)
    {
        $this->property = $property;
    }

    /**
     * @inheritdoc
     */
    public function getSalesforceId($entity): ?string
    {
        return $this->getEntityMetadata($entity)->getReflectionProperty($this->property)->getValue($entity);
    }

    /**
     * @inheritdoc
     */
    public function getEntityBySalesforceId(string $salesforceId, string $entityClass)
    {
        return $this->entityManager->getRepository($entityClass)->findOneBy([$this->property => $salesforceId]);
    }

    /**
     * @inheritdoc
     */
    public function persistSalesforceAction(string $localId, string $localType, string $salesforceId, string $action): void
    {
        switch ($action) {
            case Action::Create:
                $doctrineMetadataProperty = $this->getEntityMetadata($entity)->getReflectionProperty($this->property);
                $doctrineMetadataProperty->setValue($entity, $salesforceId);

                $this->entityManager->persist($entity);
                break;

            case Action::Delete:
                // Nothing to do - entity is deleted, so is relation to salesforce
                break;

            case Action::Update:
                break;
        }

        $this->entityManager->flush();
    }
}