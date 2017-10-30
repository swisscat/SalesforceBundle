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

    public static function invalidMappingDefinition(string $className, string $reason)
    {
        return new self(sprintf("Invalid mapping definition for class %s: %s", $className, $reason));
    }

    public static function missingDriverConfiguration(string $className, array $config)
    {
        return new self(sprintf("The following configurations are missing for class %s: %s", $className, implode(',',$config)));
    }
}