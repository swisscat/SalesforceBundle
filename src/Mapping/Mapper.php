<?php

namespace Swisscat\SalesforceBundle\Mapping;

use Doctrine\Common\Util\ClassUtils;
use Swisscat\SalesforceBundle\Mapping\Driver\DriverInterface;
use Swisscat\SalesforceBundle\Mapping\Salesforce\MappedObject;

class Mapper
{
    /**
     * @var DriverInterface
     */
    private $mappingDriver;

    /**
     * @param DriverInterface $mappingDriver
     */
    public function __construct(DriverInterface $mappingDriver)
    {
        $this->mappingDriver = $mappingDriver;
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

        $entityMapping = $this->mappingDriver->loadMetadataForClass($modelClass = ClassUtils::getRealClass(get_class($model)));

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

                $value = $model->{'get'.ucfirst($mapping['name'] ?? $fieldName)}();

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
}
