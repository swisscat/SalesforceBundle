<?php

namespace Swisscat\SalesforceBundle\Mapping\Driver;

use Swisscat\SalesforceBundle\Mapping\ClassMetadata;
use Swisscat\SalesforceBundle\Mapping\MappingException;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\FileLocatorInterface;

class XmlDriver implements DriverInterface
{
    private $fileLocator;

    public function __construct(array $paths)
    {
        $this->fileLocator = new FileLocator($paths);
    }

    public function loadMetadataForClass(string $className) : ClassMetadata
    {
        $partialClassName = substr($className, 1+strrpos($className, '\\'));

        try {
            $fileName = $this->fileLocator->locate("$partialClassName.mapping.xml");
        } catch (FileLocatorFileNotFoundException $e) {
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