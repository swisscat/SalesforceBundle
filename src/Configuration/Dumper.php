<?php

namespace Swisscat\SalesforceBundle\Configuration;

use Swisscat\SalesforceBundle\Mapping\Driver\XmlDriver;
use Symfony\Component\Yaml\Yaml;

class Dumper
{
    private $xmlDriver;

    /**
     * @param XmlDriver $xmlDriver
     */
    public function __construct(XmlDriver $xmlDriver)
    {
        $this->xmlDriver = $xmlDriver;
    }

    public function dumpTopicConfiguration(): string
    {
        $classNames = $this->xmlDriver->getAllClassNames();

        $config = ['salesforce' => ['streams' => []]];

        foreach ($classNames as $className) {
            $metadata = $this->xmlDriver->loadMetadataForClass($className);
            $config['salesforce']['streams'][] = ['type' => 'topic', 'name' => $metadata->getSalesforceType().'Topic', 'resource' => $className];
        }

        return Yaml::dump($config,3);
    }

    const SoqlTemplate = <<<EOD
PushTopic pushTopic = new PushTopic();
pushTopic.Name = '%s';
pushTopic.Query = 'SELECT %s FROM %s';
pushTopic.ApiVersion = 40.0;
pushTopic.NotifyForOperationCreate = true;
pushTopic.NotifyForOperationUpdate = true;
pushTopic.NotifyForOperationUndelete = true;
pushTopic.NotifyForOperationDelete = true;
pushTopic.NotifyForFields = 'Referenced';
insert pushTopic;
EOD;


    public function dumpSoqlConfiguration(): string
    {
        $classNames = $this->xmlDriver->getAllClassNames();

        $soqlConfig = [];

        foreach ($classNames as $className) {
            $metadata = $this->xmlDriver->loadMetadataForClass($className);

            $allFields = array_merge(['Id'], $metadata->getFieldNames());

            //TODO: Add link SF
            if (in_array('FirstName', $allFields)) {
                $allFields[] = 'Name';
            }

            $soqlConfig[] = sprintf(self::SoqlTemplate, $metadata->getSalesforceType().'Topic', implode(', ',$allFields), $metadata->getSalesforceType());
        }

        return implode(PHP_EOL, $soqlConfig);
    }
}