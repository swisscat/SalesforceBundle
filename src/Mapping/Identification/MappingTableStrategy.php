<?php

namespace Swisscat\SalesforceBundle\Mapping\Identification;

use Swisscat\SalesforceBundle\Entity\SalesforceMapping;
use Swisscat\SalesforceBundle\Mapping\Action;
use Swisscat\SalesforceBundle\Mapping\Mapper;
use Webmozart\Assert\Assert;

class MappingTableStrategy implements StrategyInterface
{
    use EntityManagedTrait;

    /**
     * @inheritdoc
     */
    public function getSalesforceId($entity): ?string
    {
        $mappingEntity = $this->getMappingEntity($entity);
        return $mappingEntity ? $mappingEntity->getSalesforceId() : null;
    }

    /**
     * @inheritdoc
     */
    public function getEntityBySalesforceId(string $salesforceId, string $entityClass)
    {
        $mappingEntity = $this->entityManager->getRepository(SalesforceMapping::class)->findOneBy([
            'entityType' => $entityClass,
            'salesforceId' => $salesforceId
        ]);

        if ($mappingEntity) {
            return $this->entityManager->getRepository($entityClass)->find($mappingEntity->getEntityId());
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function persistSalesforceAction($entity, string $salesforceId, string $action): void
    {
        switch ($action) {
            case Action::Create:
                $mappingEntity = new SalesforceMapping();
                $mappingEntity->setEntityType(Mapper::getClassRealName($entity));
                $mappingEntity->setSalesforceId($salesforceId);
                $mappingEntity->setEntityId($this->getEntityId($entity));
                $this->entityManager->persist($mappingEntity);
                break;

            case Action::Delete:
                $mappingEntity = $this->getMappingEntity($entity);
                $this->entityManager->remove($mappingEntity);
                break;

            case Action::Update:
                break;
        }

        $this->entityManager->flush();
    }

    /**
     * @param $entity
     * @return null|SalesforceMapping
     */
    private function getMappingEntity($entity): ?SalesforceMapping
    {
        $entityClass = Mapper::getClassRealName($entity);
        $entityId = $this->getEntityId($entity);

        return $this->entityManager->getRepository(SalesforceMapping::class)->findOneBy([
            'entityType' => $entityClass,
            'entityId' => $entityId
        ]);
    }

    /**
     * @param $entity
     * @return mixed
     */
    private function getEntityId($entity)
    {
        $doctrineMetadata = $this->getEntityMetadata($entity);

        Assert::same(count($identifier = $doctrineMetadata->getIdentifier()), 1);

        return $doctrineMetadata->getReflectionProperty(reset($identifier))->getValue($entity);
    }
}