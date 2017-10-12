<?php

namespace Swisscat\SalesforceBundle\Mapping;

use Swisscat\SalesforceBundle\Mapping\Driver\XmlDriver;

class Mapper
{
    /**
     * @var XmlDriver
     */
    private $mappingDriver;

    /**
     * @param XmlDriver $mappingDriver
     */
    public function __construct(XmlDriver $mappingDriver)
    {
        $this->mappingDriver = $mappingDriver;
    }

    /**
     * Map a PHP model object to a Salesforce object
     *
     * The PHP object must be properly annoated
     *
     * @param mixed $model  PHP model object
     * @return \stdClass
     */
    public function mapToSalesforceObject($model)
    {
        $sObject = new \stdClass;
        $sObject->fieldsToNull = array();

        $entityMapping = $this->mappingDriver->loadMetadataForClass(get_class($model));

        foreach ($entityMapping['fields'] as $salesforceField => $mapping) {

            $isUpdateable = true;
            $isCreateable = true;

            // If the object is created, only allow creatable fields.
            // If the object is updated, only allow updatable.
            if (($model->getId() && $isUpdateable)
                || (!$model->getId() && $isCreateable)
                // for 'Id' field:
                || $isIdLookup = true) {

                $value = $model->{'get'.ucfirst($mapping['property'] ?? $salesforceField)}();

                if (null === $value || (is_string($value) && $value === '')) {
                    // Do not set fieldsToNull on create
                    if ($model->getId()) {
                        $sObject->fieldsToNull[] = $salesforceField;
                    }
                } else {
                    $sObject->$salesforceField = $value;
                }
            }
        }

        return $sObject;
    }
}
