<?php

namespace Swisscat\SalesforceBundle\Mapping\Driver;

use Swisscat\SalesforceBundle\Mapping\ClassMetadata;
use Swisscat\SalesforceBundle\Mapping\MappingException;

class XmlDriver implements DriverInterface
{
    public function loadMetadataForClass(string $className) : ClassMetadata
    {
        $partialClassName = substr($className, 1+strrpos($className, '\\'));

        if (!file_exists($fileName = dirname(dirname(__DIR__)).'/Resources/config/salesforce/'.$partialClassName.'.mapping.xml')) {
            throw MappingException::couldNotFindMappingForClass($className);
        }

        return $this->loadClassFromMappingFile($className, $fileName);
    }

    protected function loadClassFromMappingFile(string $className, string $fileName)
    {
        $metadata = new ClassMetadata();

        try {
            $xmlElement = simplexml_load_file($fileName);
        } catch (\Exception $e) {
            throw MappingException::xmlParsingException($e);
        }

        if (isset($xmlElement->entity)) {
            foreach ($xmlElement->entity as $entityElement) {
                $entityClass = (string)$entityElement['class'];

                if ($entityClass !== $className) {
                    continue;
                }

                $metadata->setSalesforceType((string)$entityElement['object']);

                if (isset($entityElement->property)) {
                    foreach ($entityElement->property as $propertyElement) {
                        $name = (string)$propertyElement['name'];
                        $field = (string)$propertyElement['field'];
                        $metadata->setFieldMapping($field, ['name' => $name]);
                    }
                }

                if (isset($entityElement->id)) {
                    foreach ($entityElement->id as $idElement) {
                        $metadata->setIdentifier([
                            'name' => (string)$idElement['name'],
                            'externalId' => (string)$idElement['externalId'],
                        ]);
                    }
                }

                if (isset($entityElement->{'salesforce-id'})) {
                    foreach ($entityElement->{'salesforce-id'} as $salesforceIdElement) {
                        $metadata->setSalesforceIdLocalMapping([
                            'type' => (string)$salesforceIdElement['type'],
                            'property' => (string)$salesforceIdElement['property'],
                        ]);
                    }
                }

                return $metadata;
            }
        }

        throw MappingException::couldNotFindMappingForClass($className);
    }
}