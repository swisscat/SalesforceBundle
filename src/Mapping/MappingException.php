<?php

namespace Swisscat\SalesforceBundle\Mapping;

class MappingException extends \Exception
{
    public static function couldNotFindMappingForClass(string $className)
    {
        return new self(sprintf("Could not find a mapping for class '%s'", $className));
    }
}