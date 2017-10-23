<?php

namespace Swisscat\SalesforceBundle\Test\Mapper;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Swisscat\SalesforceBundle\Listener\SalesforceListener;
use Swisscat\SalesforceBundle\Mapping\Driver\XmlDriver;
use Swisscat\SalesforceBundle\Mapping\Mapper;
use Swisscat\SalesforceBundle\Producer\AmqpProducer;
use Swisscat\SalesforceBundle\Test\TestCase;
use Swisscat\SalesforceBundle\Test\TestData\Customer;
use Symfony\Component\EventDispatcher\GenericEvent;

class CustomerLocalPropertyMapperTest extends TestCase
{
    protected $listener;

    protected $amqp;

    protected $em;

    public function setUp()
    {
        $this->amqp = $this->createMock(Producer::class);
        $this->em = $this->createMock(EntityManager::class);

        $driver = new XmlDriver([dirname(__DIR__).'/TestData/local_mapping_property']);
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

        $prop = $refl->getProperty('salesforceId');
        $prop->setAccessible(true);
        $prop->setValue($customer, 'sf12345');

        $meta = new ClassMetadata(Customer::class);
        $meta->mapField(['fieldName' => 'firstName']);
        $meta->mapField(['fieldName' => 'lastName']);
        $meta->mapField(['fieldName' => 'email']);
        $meta->mapField(['fieldName' => 'id']);
        $meta->mapField(['fieldName' => 'salesforceId']);
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

}