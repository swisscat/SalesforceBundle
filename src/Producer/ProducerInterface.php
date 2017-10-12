<?php
declare(strict_types=1);

namespace Swisscat\SalesforceBundle\Producer;

interface ProducerInterface
{
    /**
     * Publish an object
     * @param mixed $object
     * @param array $additionalProperties
     * @return void
     */
    public function publish($object, $additionalProperties = array()) : void ;
}