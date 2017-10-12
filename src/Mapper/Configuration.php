<?php

namespace Swisscat\SalesforceBundle\Mapper;

use InvalidArgumentException;

class Configuration
{
    /**
     * @var bool
     */
    private $clientMapping;

    /**
     * @var string
     */
    private $salesforceIdentifier;

    /**
     * @param bool $clientMapping
     * @param string $salesforceIdentifier
     */
    public function __construct(bool $clientMapping, string $salesforceIdentifier = '')
    {
        if (!$salesforceIdentifier && !$clientMapping) {
            throw new InvalidArgumentException('mappingNotDefined');
        }

        $this->clientMapping = $clientMapping;
        $this->salesforceIdentifier = $salesforceIdentifier;
    }

    public function hasClientMapping()
    {
        return $this->clientMapping;
    }

    public function hasSalesforceMapping()
    {
        return (bool)$this->salesforceIdentifier;
    }

    public function getSalesforceIdentifier()
    {
        return $this->salesforceIdentifier;
    }
}