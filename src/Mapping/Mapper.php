<?php

namespace Swisscat\SalesforceBundle\Mapping;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Swisscat\SalesforceBundle\Entity\SalesforceMapping;
use Swisscat\SalesforceBundle\Mapping\Driver\DriverInterface;
use Swisscat\SalesforceBundle\Mapping\Salesforce\MappedObject;

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
     * @param mixed $model  PHP model object
     * @return MappedObject
     */
    public function mapToSalesforceObject($model)
    {
        $sObject = new \stdClass;
        $sObject->fieldsToNull = array();

        $entityMapping = $this->mappingDriver->loadMetadataForClass($modelClass = $this->getClassRealName($model));

        $doctrineMetadata = $this->entityManager->getClassMetadata($modelClass);

        foreach ($entityMapping->getFieldNames() as $fieldName) {

            $mapping = $entityMapping->getFieldMapping($fieldName);

            $isUpdateable = true;
            $isCreateable = true;

            // If the object is created, only allow creatable fields.
            // If the object is updated, only allow updatable.
            if (($model->getId() && $isUpdateable)
                || (!$model->getId() && $isCreateable)
                // for 'Id' field:
                || $isIdLookup = true) {

                $value = $doctrineMetadata->reflFields[$mapping['name'] ?? $fieldName]->getValue($model);

                if (null === $value || (is_string($value) && $value === '')) {
                    // Do not set fieldsToNull on create
                    if ($model->getId()) {
                        $sObject->fieldsToNull[] = $fieldName;
                    }
                } else {
                    $sObject->$fieldName = $value;
                }
            }
        }

        return new MappedObject($sObject, $model->getId(), $modelClass, $entityMapping->getSalesforceType());
    }

    public function getEntity(string $entityType, string $salesforceId)
    {
        $metadata = $this->mappingDriver->loadMetadataForClass($entityType);

        if (false  === $localMapping = $metadata->getSalesforceIdLocalMapping()) {
            return null;
        }

        switch ($localMapping['type']) {
            case 'mappingTable':
                $salesforceMappingObject = $this->entityManager->getRepository(SalesforceMapping::class)->findOneBy(compact('salesforceId', 'entityType'));
                $entity = $this->entityManager->getRepository($entityType)->find($salesforceMappingObject->getEntityId());
                break;

            case 'property':
                $entity = $this->entityManager->getRepository($entityType)->findOneBy([$localMapping['property'] => $salesforceId]);
                break;

            default:
                throw MappingException::invalidMappingDefinition($entityType, "Invalid local mapping type");
        }

        return $entity;
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
     * @param $entity
     * @return string
     */
    private function getClassRealName($entity): string
    {
        return ClassUtils::getRealClass(get_class($entity));
    }
}
