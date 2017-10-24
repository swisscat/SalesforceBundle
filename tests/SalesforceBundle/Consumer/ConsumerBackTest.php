<?php

namespace Swisscat\SalesforceBundle\Test\Consumer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Swisscat\SalesforceBundle\Consumer\SalesforceBack;
use Swisscat\SalesforceBundle\Mapping\Driver\XmlDriver;
use Swisscat\SalesforceBundle\Test\Mapper\CustomerLocalPropertyMapperTest;
use Swisscat\SalesforceBundle\Test\TestCase;
use Swisscat\SalesforceBundle\Test\TestData\Customer;

class ConsumerBackTest extends TestCase
{
    /**
     * @var SalesforceBack
     */
    private $consumer;

    private $logger;

    private $em;

    public function setUp()
    {
        $this->em = $this->createMock(EntityManager::class);
        $driver = new XmlDriver([dirname(__DIR__).'/TestData/local_mapping_property']);
        $driver->setEntityManager($this->em);

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->consumer = new SalesforceBack([['name' => 'TestTopic', 'type' => 'topic', 'resource' => 'Swisscat\SalesforceBundle\Test\TestData\Customer']], $this->em, $driver, $this->logger);
    }

    public function testInvalidJsonExecution()
    {
        $message = new AMQPMessage('{invalidJson}');

        $this->logger->expects($this->once())
            ->method('log')
            ->with(LogLevel::INFO, 'Invalid JSON');

        $this->assertEquals($this->consumer->execute($message), false);
    }

    /**
     * @dataProvider jsonMessageProvider
     */
    public function testInvalidJson($json, $exceptionMessage)
    {
        $message = new AMQPMessage($json);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->assertEquals($this->consumer->execute($message), false);
    }

    public function testLoggingOnNoEntity()
    {
        $this->logger->expects($this->once())
            ->method('log')
            ->with(LogLevel::INFO, 'No local storage');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repoMock = $this->createMock(EntityRepository::class));

        $repoMock->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $message = new AMQPMessage('{"event":{"stream":"/topic/TestTopic"},"sobject":{"Id":"sf1234"}}');

        $this->consumer->execute($message);
    }

    public function testEntityProperlyUpdated()
    {
        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repoMock = $this->createMock(EntityRepository::class));

        $repoMock->expects($this->once())
            ->method('findOneBy')
            ->willReturn($customer = new Customer());

        $metadata = \Swisscat\SalesforceBundle\Test\Producer\TestCase::getCustomerMetadata();

        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $message = new AMQPMessage('{"event":{"stream":"/topic/TestTopic"},"sobject":{"Id":"sf1234","FirstName":"Test","LastName":"Consumer","Email":"test@test.com"}}');

        $this->consumer->execute($message);
    }

    public function jsonMessageProvider()
    {
        return [
            ['{}', 'Expected the key "event" to exist.'],
            ['{"event":{"stream":null}}', 'Unsupported'],
            ['{"event":{}}', 'Expected the key "stream" to exist.'],
            ['{"event":{"stream":"/topic/invalidTopic"}}', 'Topic not found in configuration: invalidTopic'],
            ['{"event":{"stream":"/topic/TestTopic"}}', 'Expected the key "sobject" to exist.'],
            ['{"event":{"stream":"/topic/TestTopic"},"sobject":{}}', 'Expected the key "Id" to exist.'],
        ];
    }
}