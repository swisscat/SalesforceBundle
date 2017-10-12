<?php

namespace Swisscat\SalesforceBundle\Producer;

use Swisscat\SalesforceBundle\Mapping\MappingException;

class ProducerException extends \Exception
{
    public static function fromMappingException(MappingException $e) {
        return new self("A mapping exception occured", 0, $e);
    }

    public static function fromAmqpPublishException(\Throwable $e) {
        return new self("Object publication failed", 0, $e);
    }
}