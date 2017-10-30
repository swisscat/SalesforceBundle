<?php

namespace Swisscat\SalesforceBundle\Mapping\Driver;

use Doctrine\ORM\EntityManagerInterface;
use Swisscat\SalesforceBundle\Mapping\ClassMetadata;
use Swisscat\SalesforceBundle\Mapping\Identification\EntityManagedTrait;
use Swisscat\SalesforceBundle\Mapping\Identification\FullRemoteStrategy;
use Swisscat\SalesforceBundle\Mapping\Identification\MappingTableStrategy;
use Swisscat\SalesforceBundle\Mapping\Identification\PropertyStrategy;
use Swisscat\SalesforceBundle\Mapping\MappingException;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\FileLocatorInterface;

class XmlDriver implements DriverInterface
{
    private $fileLocator;

    private $entityManager;

    public function __construct(array $paths)
    {
        $this->fileLocator = new FileLocator($paths);
    }

    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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
                $this->setFieldMappings($metadata, $entityElement);
                $this->setIdentificationStrategies($metadata, $entityElement);

                return $metadata;
            }
        }

        throw MappingException::couldNotFindMappingForClass($className);
    }

    private function setFieldMappings(ClassMetadata $metadata, \SimpleXMLElement $element)
    {
        if (isset($element->property)) {
            foreach ($element->property as $propertyElement) {
                $name = (string)$propertyElement['name'];
                $field = (string)$propertyElement['field'];
                $metadata->setFieldMapping($field, ['name' => $name]);
            }
        }
    }

    /**
     * @param ClassMetadata $metadata
     * @param \SimpleXMLElement $element
     *
     * @throws MappingException
     */
    private function setIdentificationStrategies(ClassMetadata $metadata, \SimpleXMLElement $element)
    {
        if (isset($element->{'identification-strategies'}->{'strategy'})) {
            foreach ($element->{'identification-strategies'}->{'strategy'} as $strategyElement) {
                $strategyClass = (string)$strategyElement['class'];

                if (!class_exists($strategyClass)) {
                    throw MappingException::invalidMappingDefinition((string)$element['class'], sprintf("Invalid identification strategy '%s'", $strategyClass));
                }
                $strategy = new $strategyClass();

                if (isset(class_uses($strategy)[EntityManagedTrait::class])) {
                    if ($this->entityManager === null) {
                        throw MappingException::missingDriverConfiguration((string)$element['class'], ['EntityManager']);
                    }

                    $strategy->setEntityManager($this->entityManager);
                }

                if ($strategy instanceof PropertyStrategy) {
                    $strategy->setProperty((string)$strategyElement['property']);
                }

                if ($strategy instanceof FullRemoteStrategy) {
                    if (isset($strategyElement['matchingField'])) {
                        $strategy->setMatchingField((string)$strategyElement['matchingField']);
                    }
                }

                $metadata->addIdentificationStrategy($strategy);
            }
        }
    }
}