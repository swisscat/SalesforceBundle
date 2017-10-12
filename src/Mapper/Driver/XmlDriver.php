<?php

namespace Swisscat\SalesforceBundle\Mapper\Driver;

class XmlDriver
{
    public function loadMetadataForClass($className)
    {
        return [
            'fields' => [
                'FirstName' => ['property' => 'firstName'],
                'LastName' => ['property' => 'lastName'],
                'Email' => ['property' => 'email'],
            ]
        ];
        // TODO: Implement loadMetadataForClass() method.
    }

    /**
     * {@inheritDoc}
     */
    protected function loadMappingFile($file)
    {
        $result = [];
        $xmlElement = simplexml_load_file($file);

        if (isset($xmlElement->entity)) {
            foreach ($xmlElement->entity as $entityElement) {
                $entityName = (string)$entityElement['name'];
                $result[$entityName] = $entityElement;
            }
        }

        return $result;
    }
}