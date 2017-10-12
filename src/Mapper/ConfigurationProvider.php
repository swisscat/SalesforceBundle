<?php

namespace Swisscat\SalesforceBundle\Mapper;


class ConfigurationProvider
{
    /**
     * @var array
     */
    private $configuration;

    public function __construct(array $configuration = [])
    {
        $this->configuration = $configuration;

        $this->configuration = [
            'default' => [
                'client' => true,
                'salesforce_id' => false,
            ],
        ];
    }

    public function getMappingInformation(string $class)
    {
        $classSpecificConfig = $this->configuration[$class] ?? [];
        $config = array_replace_recursive($classSpecificConfig, $this->configuration['default']);
        return new Configuration($config['client'], $config['salesforce_id']);
    }

}