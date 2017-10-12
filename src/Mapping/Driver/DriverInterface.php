<?php

namespace Swisscat\SalesforceBundle\Mapping\Driver;

use Swisscat\SalesforceBundle\Mapping\ClassMetadata;

interface DriverInterface
{
    /**
     * @param string $className
     * @return ClassMetadata
     */
    public function loadMetadataForClass(string $className) : ClassMetadata ;
}