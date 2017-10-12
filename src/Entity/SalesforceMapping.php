<?php

namespace Swisscat\SalesforceBundle\Entity;

class SalesforceMapping
{
    /**
     * @var mixed|null
     */
    protected $id;

    /**
     * @var string|null
     */
    protected $salesforceId;

    /**
     * @var string|null
     */
    protected $entityType;

    /**
     * @var mixed|null
     */
    protected $entityId;

    /**
     * @return mixed|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed|null $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return null|string
     */
    public function getSalesforceId()
    {
        return $this->salesforceId;
    }

    /**
     * @param null|string $salesforceId
     */
    public function setSalesforceId($salesforceId)
    {
        $this->salesforceId = $salesforceId;
    }

    /**
     * @return null|string
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * @param null|string $entityType
     */
    public function setEntityType($entityType)
    {
        $this->entityType = $entityType;
    }

    /**
     * @return mixed|null
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param mixed|null $entityId
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;
    }
}