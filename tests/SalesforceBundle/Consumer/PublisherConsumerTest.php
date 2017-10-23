<?php

namespace Swisscat\SalesforceBundle\Test\Consumer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PhpAmqpLib\Message\AMQPMessage;
use Phpforce\SoapClient\BulkSaver;
use Phpforce\SoapClient\Result\SaveResult;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Swisscat\SalesforceBundle\Consumer\SalesforceBack;
use Swisscat\SalesforceBundle\Consumer\SalesforcePublisherConsumer;
use Swisscat\SalesforceBundle\Mapping\Driver\XmlDriver;
use Swisscat\SalesforceBundle\Mapping\Mapper;
use Swisscat\SalesforceBundle\Test\Mapper\CustomerLocalPropertyMapperTest;
use Swisscat\SalesforceBundle\Test\TestCase;
use Swisscat\SalesforceBundle\Test\TestData\Customer;

class PublisherConsumerTest extends TestCase
{
    /**
     * @var SalesforcePublisherConsumer
     */
    private $consumer;

    /**
     * @var
     */
    private $logger;

    private $em;

    private $mapper;

    private $driver;

    private $saver;

    public function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em = $this->createMock(EntityManager::class);
        $this->driver = new XmlDriver([dirname(__DIR__).'/TestData']);
        $this->driver->setEntityManager($this->em);

        $this->consumer = new SalesforcePublisherConsumer($this->mapper = new Mapper($this->driver, $this->em), $this->logger, $this->saver = $this->createMock(BulkSaver::class));
    }

    public function testInvalidMessagesAreLogged()
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Invalid message content');

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Message body: {{');

        $this->consumer->batchExecute([new AMQPMessage('{{')]);
    }

    public function testCreateMessage()
    {
        $saveResult = new SaveResult();
        $refl = new \ReflectionClass(SaveResult::class);

        $p = $refl->getProperty('id');
        $p->setAccessible(true);
        $p->setValue($saveResult, 'sf1234');

        $p = $refl->getProperty('success');
        $p->setAccessible(true);
        $p->setValue($saveResult, true);

        $this->saver->expects($this->once())
            ->method('flush')
            ->willReturn([[$saveResult]]);


        [$customer,$meta] = CustomerLocalPropertyMapperTest::generateCreateCustomerData();

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($meta);

        $this->consumer->batchExecute([new AMQPMessage('{"salesforce":{"sObject":{"fieldsToNull":[],"FirstName":"First","LastName":"Last","Email":"customer@test.com"},"type":"Contact"},"local":{"id":"10","type":"Swisscat\\\\SalesforceBundle\\\\Test\\\\TestData\\\\Customer"},"action":"create"}')]);
    }


    public function testUpdateMessage()
    {
        $saveResult = new SaveResult();
        $refl = new \ReflectionClass(SaveResult::class);

        $p = $refl->getProperty('id');
        $p->setAccessible(true);
        $p->setValue($saveResult, 'sf1234');

        $p = $refl->getProperty('success');
        $p->setAccessible(true);
        $p->setValue($saveResult, true);

        $this->saver->expects($this->once())
            ->method('flush')
            ->willReturn([[$saveResult]]);


        [$customer,$meta] = CustomerLocalPropertyMapperTest::generateCreateCustomerData();

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($meta);

        $this->consumer->batchExecute([new AMQPMessage('{"salesforce":{"sObject":{"fieldsToNull":[],"id":"sf1234","FirstName":"First","LastName":"Last","Email":"customer@test.com"},"type":"Contact"},"local":{"id":"10","type":"Swisscat\\\\SalesforceBundle\\\\Test\\\\TestData\\\\Customer"},"action":"update"}')]);
    }

    public function testDeleteMessage()
    {
        $saveResult = new SaveResult();
        $refl = new \ReflectionClass(SaveResult::class);

        $p = $refl->getProperty('id');
        $p->setAccessible(true);
        $p->setValue($saveResult, 'sf1234');

        $p = $refl->getProperty('success');
        $p->setAccessible(true);
        $p->setValue($saveResult, true);

        $this->saver->expects($this->once())
            ->method('flush')
            ->willReturn([[$saveResult]]);


        [$customer,$meta] = CustomerLocalPropertyMapperTest::generateCreateCustomerData();

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($meta);

        $this->consumer->batchExecute([new AMQPMessage('{"salesforce":{"sObject":{"fieldsToNull":[],"FirstName":"First","LastName":"Last","Email":"customer@test.com"},"type":"Contact"},"local":{"id":"10","type":"Swisscat\\\\SalesforceBundle\\\\Test\\\\TestData\\\\Customer"},"action":"delete"}')]);
    }

    public function testLocalMappingUpdate()
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em = $this->createMock(EntityManager::class);
        $this->driver = new XmlDriver([dirname(__DIR__).'/TestData/local_mapping_property']);
        $this->driver->setEntityManager($this->em);

        $this->consumer = new SalesforcePublisherConsumer($this->mapper = new Mapper($this->driver, $this->em), $this->logger, $this->saver = $this->createMock(BulkSaver::class));

        $saveResult = new SaveResult();
        $refl = new \ReflectionClass(SaveResult::class);

        $p = $refl->getProperty('id');
        $p->setAccessible(true);
        $p->setValue($saveResult, 'sf1234');

        $p = $refl->getProperty('success');
        $p->setAccessible(true);
        $p->setValue($saveResult, true);

        $this->saver->expects($this->once())
            ->method('flush')
            ->willReturn([[$saveResult]]);


        [$customer,$meta] = CustomerLocalPropertyMapperTest::generateCreateCustomerData();

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($meta);

        $this->consumer->batchExecute([new AMQPMessage('{"salesforce":{"sObject":{"fieldsToNull":[],"id":"sf1234","FirstName":"First","LastName":"Last","Email":"customer@test.com"},"type":"Contact"},"local":{"id":"10","type":"Swisscat\\\\SalesforceBundle\\\\Test\\\\TestData\\\\Customer"},"action":"update"}')]);
    }

    public function testLocalMappingCreate()
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em = $this->createMock(EntityManager::class);
        $this->driver = new XmlDriver([dirname(__DIR__).'/TestData/local_mapping_property']);
        $this->driver->setEntityManager($this->em);

        $this->consumer = new SalesforcePublisherConsumer($this->mapper = new Mapper($this->driver, $this->em), $this->logger, $this->saver = $this->createMock(BulkSaver::class));

        $saveResult = new SaveResult();
        $refl = new \ReflectionClass(SaveResult::class);

        $p = $refl->getProperty('id');
        $p->setAccessible(true);
        $p->setValue($saveResult, 'sf1234');

        $p = $refl->getProperty('success');
        $p->setAccessible(true);
        $p->setValue($saveResult, true);

        $this->saver->expects($this->once())
            ->method('flush')
            ->willReturn([[$saveResult]]);


        [$customer,$meta] = CustomerLocalPropertyMapperTest::generateCreateCustomerData();

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($meta);

        $this->em->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo= $this->createMock(EntityRepository::class));

        $repo->expects($this->once())
            ->method('find')
            ->willReturn($cs = new Customer());

        $this->consumer->batchExecute([new AMQPMessage('{"salesforce":{"sObject":{"fieldsToNull":[],"id":"sf1234","FirstName":"First","LastName":"Last","Email":"customer@test.com"},"type":"Contact"},"local":{"id":"10","type":"Swisscat\\\\SalesforceBundle\\\\Test\\\\TestData\\\\Customer"},"action":"create"}')]);
    }


    public function testLocalDeleteMessage()
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em = $this->createMock(EntityManager::class);
        $this->driver = new XmlDriver([dirname(__DIR__).'/TestData/local_mapping_property']);
        $this->driver->setEntityManager($this->em);

        $this->consumer = new SalesforcePublisherConsumer($this->mapper = new Mapper($this->driver, $this->em), $this->logger, $this->saver = $this->createMock(BulkSaver::class));

        $saveResult = new SaveResult();
        $refl = new \ReflectionClass(SaveResult::class);

        $p = $refl->getProperty('id');
        $p->setAccessible(true);
        $p->setValue($saveResult, 'sf1234');

        $p = $refl->getProperty('success');
        $p->setAccessible(true);
        $p->setValue($saveResult, true);

        $this->saver->expects($this->once())
            ->method('flush')
            ->willReturn([[$saveResult]]);


        [$customer,$meta] = CustomerLocalPropertyMapperTest::generateCreateCustomerData();

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($meta);

        $this->consumer->batchExecute([new AMQPMessage('{"salesforce":{"sObject":{"fieldsToNull":[],"FirstName":"First","LastName":"Last","Email":"customer@test.com"},"type":"Contact"},"local":{"id":"10","type":"Swisscat\\\\SalesforceBundle\\\\Test\\\\TestData\\\\Customer"},"action":"delete"}')]);
    }
}