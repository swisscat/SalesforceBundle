<?php

namespace Swisscat\SalesforceBundle\Mapping;

class MappingException extends \Exception
{
    public static function couldNotFindMappingForClass(string $className)
    {
        return new self(sprintf("Could not find a mapping for class '%s'", $className));
    }

    public static function xmlParsingException(\Exception $e)
    {
        return new self("XML parse failure", 0, $e);
    }
}