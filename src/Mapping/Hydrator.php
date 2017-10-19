<?php

namespace Swisscat\SalesforceBundle\Mapping;

use Doctrine\ORM\EntityManagerInterface;

class Hydrator
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function hydrateFromArray($entity, array $data = [])
    {
        $class = $this->entityManager->getClassMetadata(get_class($entity));

        foreach ($data as $field => $value) {
            if (isset($class->fieldMappings[$field])) {
                $class->reflFields[$field]->setValue($entity, $value);
            }
        }

        return $entity;
    }
}