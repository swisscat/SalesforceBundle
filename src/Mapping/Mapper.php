<?php

namespace Swisscat\SalesforceBundle\Mapping;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Swisscat\SalesforceBundle\Mapping\Driver\DriverInterface;
use Swisscat\SalesforceBundle\Mapping\Salesforce\SyncEvent;

class Mapper
{
    /**
     * @var DriverInterface
     */
    private $mappingDriver;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param DriverInterface $mappingDriver
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(DriverInterface $mappingDriver, EntityManagerInterface $entityManager)
    {
        $this->mappingDriver = $mappingDriver;
        $this->entityManager = $entityManager;
    }

    /**
     * Map a PHP model object to a Salesforce object
     *
     * The PHP object must be properly annoated
     *
     * @param mixed $entity  PHP model object
     * @param string $action
     * @return SyncEvent
     */
    public function mapToSalesforceObject($entity, string $action)
    {
        $sObject = new \stdClass;
        $sObject->fieldsToNull = array();

        $entityMapping = $this->mappingDriver->loadMetadataForClass($modelClass = $this->getClassRealName($entity));

        $doctrineMetadata = $this->entityManager->getClassMetadata($modelClass);

        $entityId = $doctrineMetadata->getReflectionProperty($doctrineMetadata->getIdentifier()[0])->getValue($entity);

        foreach ($entityMapping->getFieldNames() as $fieldName) {
            $mapping = $entityMapping->getFieldMapping($fieldName);

            $value = $doctrineMetadata->getReflectionProperty($mapping['name'] ?? $fieldName)->getValue($entity);

            if (null === $value || (is_string($value) && $value === '')) {
                $sObject->fieldsToNull[] = $fieldName;
            } else {
                $sObject->$fieldName = $value;
            }
        }

        switch ($action) {
            case Action::Create:
                // No need to find SalesforceID on creations
                break;
            
            case Action::Update:
            case Action::Delete:
                if (null === $salesforceId = $this->getSalesforceId($entity)) {
                    throw new \LogicException('Invalid Mapping State');
                }

                $sObject->id = $salesforceId;
                break;
        }

        return SyncEvent::fromArray([
            'salesforce' => [
                'sObject' => $sObject,
                'type' => $entityMapping->getSalesforceType(),
            ],
            'local' => [
                'id' => $entityId,
                'type' => $modelClass,
            ],
            'action' => $action
        ]);
    }

    /**
     * @param $entity
     * @return string|null
     */
    private function getSalesforceId($entity)
    {
        $metadata = $this->mappingDriver->loadMetadataForClass($className = $this->getClassRealName($entity));

        foreach ($metadata->getIdentificationStrategies() as $identificationStrategy) {
            if (null !== $salesforceId = $identificationStrategy->getSalesforceId($entity)) {
                return $salesforceId;
            }
        }

        return null;
    }

    public function getEntity(string $entityType, string $salesforceId)
    {
        $metadata = $this->mappingDriver->loadMetadataForClass($entityType);

        foreach ($metadata->getIdentificationStrategies() as $identificationStrategy) {
            if (null !== $entity = $identificationStrategy->getEntityBySalesforceId($salesforceId, $entityType)) {
                return $entity;
            }
        }

        return null;
    }

    public function mapFromSalesforceObject($sObject, $entity)
    {
        $entityMapping = $this->mappingDriver->loadMetadataForClass($mappedClass = $this->getClassRealName($entity));

        $doctrineMetadata = $this->entityManager->getClassMetadata($mappedClass);

        foreach ($entityMapping->getFieldNames() as $fieldName) {

            $mapping = $entityMapping->getFieldMapping($fieldName);

            $doctrineMetadata->reflFields[$mapping['name'] ?? $fieldName]->setValue($entity, $sObject->$fieldName);
        }

        return $entity;
    }

    public function validateMapping(string $className)
    {
        $doctrineMetadata = $this->entityManager->getClassMetadata($className);

        $salesforceMetadata = $this->mappingDriver->loadMetadataForClass($className);

        foreach ($salesforceMetadata->getFieldNames() as $fieldName) {
            if (!isset($doctrineMetadata->reflFields[$mapping['name'] ?? $fieldName])) {
                throw MappingException::invalidMappingDefinition($className, "Field does not exist");
            }
        }
    }

    /**
     * @param string $className
     * @return ClassMetadata
     */
    public function loadMetadataForClass(string $className)
    {
        return $this->mappingDriver->loadMetadataForClass($className);
    }

    /**
     * @param $entity
     * @return string
     */
    public static function getClassRealName($entity): string
    {
        return ClassUtils::getRealClass(get_class($entity));
    }
}
