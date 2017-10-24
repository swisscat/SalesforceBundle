<?php

namespace Swisscat\SalesforceBundle\Test\Consumer;

use Doctrine\ORM\EntityManagerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Phpforce\SoapClient\BulkSaver;
use Phpforce\SoapClient\Result\SaveResult;
use Psr\Log\LoggerInterface;
use Swisscat\SalesforceBundle\Consumer\SalesforcePublisherConsumer;
use Swisscat\SalesforceBundle\Mapping\Driver\XmlDriver;
use Swisscat\SalesforceBundle\Mapping\Mapper;

class TestCase extends \Swisscat\SalesforceBundle\Test\TestCase
{
    /**
     * @param $mappingDir
     * @return \PHPUnit_Framework_MockObject_MockObject[]
     */
    protected function createConsumer($mappingDir)
    {
        $driver = new XmlDriver([dirname(__DIR__).'/TestData/MappingConfigurations/'.$mappingDir]);
        $driver->setEntityManager($em = $this->createMock(EntityManagerInterface::class));

        $em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn(\Swisscat\SalesforceBundle\Test\Producer\TestCase::getCustomerMetadata());

        return [new SalesforcePublisherConsumer($mapper = new Mapper($driver, $em), $logger = $this->createMock(LoggerInterface::class), $saver = $this->createMock(BulkSaver::class)), $saver, $em];
    }

    protected function generateSaveResult(array $params)
    {
        $saveResult = new SaveResult();
        $refl = new \ReflectionClass(SaveResult::class);

        foreach ($params as $property => $value) {
            $p = $refl->getProperty($property);
            $p->setAccessible(true);
            $p->setValue($saveResult,$value);
        }

        return $saveResult;
    }

    /**
     * @param array $params
     * @return AMQPMessage
     */
    protected function generateAmqpMessage(array $params)
    {
        return new AMQPMessage(json_encode(array_replace_recursive([
            'salesforce' => [
                'sObject'=> [
                    'fieldsToNull' => [],
                    'FirstName' => 'First',
                    'LastName' => 'Last',
                    'Email' => 'customer@test.com'
                ],
                'type' => 'Contact',
            ],
            'local' => [
                'id' => 10,
                'type' => 'Swisscat\\SalesforceBundle\\Test\\TestData\\Customer'
            ]
        ], $params)));
    }
}