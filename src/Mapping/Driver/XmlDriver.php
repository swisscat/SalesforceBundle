<?php

namespace Swisscat\SalesforceBundle\Mapping\Driver;

use Swisscat\SalesforceBundle\Mapping\MappingException;

class XmlDriver
{
    public function loadMetadataForClass($className)
    {
        $partialClassName = substr($className, 1+strrpos($className, '\\'));

        if (!file_exists($fileName = dirname(dirname(__DIR__)).'/Resources/config/salesforce/'.$partialClassName.'.mapping.xml')) {
            throw MappingException::couldNotFindMappingForClass($className);
        }

        $mapping = $this->loadMappingFile($fileName);

        return reset($mapping);
    }

    /**
     * {@inheritDoc}
     */
    protected function loadMappingFile($file)
    {
        $result = [];
        $xmlElement = simplexml_load_file($file);

        if (isset($xmlElement->entity)) {
            foreach ($xmlElement->entity as $entityElement) {
                $entityName = (string)$entityElement['object'];
                $result[$entityName] = [];

                if (isset($entityElement->property)) {
                    foreach ($entityElement->property as $propertyElement) {
                        $name = (string)$propertyElement['name'];
                        $field = (string)$propertyElement['field'];
                        $result[$entityName]['fields'][$field] = $name;
                    }
                }
            }
        }

        return $result;
    }
}