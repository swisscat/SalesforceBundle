<?php

namespace Swisscat\SalesforceBundle\Test\Consumer;

use Doctrine\ORM\EntityManager;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Swisscat\SalesforceBundle\Consumer\SalesforceBack;
use Swisscat\SalesforceBundle\Mapping\Driver\XmlDriver;
use Swisscat\SalesforceBundle\Test\TestCase;

class ConsumerBackTest extends TestCase
{
    /**
     * @var SalesforceBack
     */
    private $consumer;

    /**
     * @var
     */
    private $logger;

    public function setUp()
    {
        $em = $this->createMock(EntityManager::class);
        $driver = new XmlDriver([dirname(__DIR__).'/TestData/local_mapping_property']);
        $driver->setEntityManager($em);

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->consumer = new SalesforceBack([['name' => 'TestTopic', 'type' => 'topic', 'resource' => 'Sylius\Component\Core\Model\Customer']], $em, $driver, $this->logger);
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