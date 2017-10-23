<?php

namespace Swisscat\SalesforceBundle\Mapping\Identification;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Swisscat\SalesforceBundle\Mapping\Mapper;

trait EntityManagedTrait
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param $entity
     * @return ClassMetadata
     */
    public function getEntityMetadata($entity): ClassMetadata
    {
        return $this->entityManager->getClassMetadata(Mapper::getClassRealName($entity));
    }
}