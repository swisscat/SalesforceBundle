<?php

namespace Swisscat\SalesforceBundle\Test\Producer;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Swisscat\SalesforceBundle\Entity\SalesforceMapping;
use Swisscat\SalesforceBundle\Listener\SalesforceListener;
use Swisscat\SalesforceBundle\Mapping\Driver\XmlDriver;
use Swisscat\SalesforceBundle\Mapping\Mapper;
use Swisscat\SalesforceBundle\Producer\AmqpProducer;
use Swisscat\SalesforceBundle\Producer\ProducerException;
use Swisscat\SalesforceBundle\Test\TestData\Customer;
use Swisscat\SalesforceBundle\Test\TestCase;
use Symfony\Component\EventDispatcher\GenericEvent;

class CustomerProducerTest extends TestCase
{
    protected $listener;

    protected $amqp;

    protected $em;

    public function setUp()
    {
        $this->amqp = $this->createMock(Producer::class);
        $this->em = $this->createMock(EntityManager::class);

        $driver = new XmlDriver([dirname(__DIR__).'/TestData']);
        $driver->setEntityManager($this->em);
        $this->listener = new SalesforceListener(new AmqpProducer(new Mapper($driver, $this->em),$this->amqp));
    }

    private function generateCreateCustomerData()
    {
        $customer = new Customer();

        $refl = new \ReflectionClass($customer);

        $prop = $refl->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($customer, 10);

        $prop = $refl->getProperty('firstName');
        $prop->setAccessible(true);
        $prop->setValue($customer, 'First');

        $prop = $refl->getProperty('lastName');
        $prop->setAccessible(true);
        $prop->setValue($customer, 'Last');

        $prop = $refl->getProperty('email');
        $prop->setAccessible(true);
        $prop->setValue($customer, 'customer@test.com');

        $meta = new ClassMetadata(Customer::class);
        $meta->mapField(['fieldName' => 'firstName']);
        $meta->mapField(['fieldName' => 'lastName']);
        $meta->mapField(['fieldName' => 'email']);
        $meta->mapField(['fieldName' => 'id']);
        $meta->setIdentifier(['id']);
        $meta->wakeupReflection(new RuntimeReflectionService());

        return [$customer, $meta];
    }

    public function testCreateCustomerPublishEvent()
    {
        [$customer,$meta] = $this->generateCreateCustomerData();

        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($meta);

        $this->amqp->expects($this->once())
            ->method('publish')
            ->with('{"salesforce":{"sObject":{"fieldsToNull":[],"FirstName":"First","LastName":"Last","Email":"customer@test.com"},"type":"Contact"},"local":{"id":"10","type":"Swisscat\\\\SalesforceBundle\\\\Test\\\\TestData\\\\Customer"},"action":"create"}');

        $this->listener->publishCreateEvent(new GenericEvent($customer));
    }

    public function testInvalidUpdateWithoutSalesforceId()
    {
        [$customer,$meta] = $this->generateCreateCustomerData();

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($meta);

        $repo = $this->createMock(EntityRepository::class);

        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid Mapping State');

        $evt = new GenericEvent($customer);

        $this->listener->publishUpdateEvent($evt);
    }

    public function testUpdateCustomerPublishEvent()
    {
        [$customer,$meta] = $this->generateCreateCustomerData();

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($meta);

        $repo = $this->createMock(EntityRepository::class);

        $mapping = new SalesforceMapping();
        $mapping->setSalesforceId('sf1234');

        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($mapping);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $this->amqp->expects($this->once())
            ->method('publish')
            ->with('{"salesforce":{"sObject":{"fieldsToNull":[],"FirstName":"First","LastName":"Last","Email":"customer@test.com","id":"sf1234"},"type":"Contact"},"local":{"id":"10","type":"Swisscat\\\\SalesforceBundle\\\\Test\\\\TestData\\\\Customer"},"action":"update"}');

        $evt = new GenericEvent($customer);
        $this->listener->publishUpdateEvent($evt);
    }

    public function testDeleteCustomerPublishEvent()
    {
        [$customer,$meta] = $this->generateCreateCustomerData();

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($meta);

        $repo = $this->createMock(EntityRepository::class);

        $mapping = new SalesforceMapping();
        $mapping->setSalesforceId('sf1234');

        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($mapping);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $this->amqp->expects($this->once())
            ->method('publish')
            ->with('{"salesforce":{"sObject":{"fieldsToNull":[],"FirstName":"First","LastName":"Last","Email":"customer@test.com","id":"sf1234"},"type":"Contact"},"local":{"id":"10","type":"Swisscat\\\\SalesforceBundle\\\\Test\\\\TestData\\\\Customer"},"action":"delete"}');

        $evt = new GenericEvent($customer);
        $this->listener->publishDeleteEvent($evt);
    }

    public function testMapperFailureOnPublication()
    {
        $driver = new XmlDriver([dirname(__DIR__).'/TestData/Invalid']);
        $driver->setEntityManager($this->em);
        $listener = new SalesforceListener(new AmqpProducer(new Mapper($driver, $this->em),$this->amqp));

        [$customer,$meta] = $this->generateCreateCustomerData();

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($meta);

        $this->expectException(ProducerException::class);
        $this->expectExceptionMessage("A mapping exception occured");

        $evt = new GenericEvent($customer);

        $listener->publishUpdateEvent($evt);
    }

    public function testUpdateCustomerPublishEventFailsOnAmqp()
    {
        [$customer,$meta] = $this->generateCreateCustomerData();

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($meta);

        $repo = $this->createMock(EntityRepository::class);

        $mapping = new SalesforceMapping();
        $mapping->setSalesforceId('sf1234');

        $repo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($mapping);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $this->amqp->expects($this->once())
            ->method('publish')
            ->willThrowException(new \Exception());

        $this->expectException(ProducerException::class);
        $this->expectExceptionMessage("Object publication failed");

        $evt = new GenericEvent($customer);
        $this->listener->publishUpdateEvent($evt);
    }
}