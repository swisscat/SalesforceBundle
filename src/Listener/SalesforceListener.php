<?php

namespace Swisscat\SalesforceBundle\Listener;

use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Swisscat\SalesforceBundle\Mapping\Mapper;
use Sylius\Component\Core\Model\Customer;
use Symfony\Component\EventDispatcher\GenericEvent;
use Webmozart\Assert\Assert;

class SalesforceListener
{
    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var Mapper
     */
    private $mapper;

    public function __construct(ProducerInterface $producer, Mapper $mapper)
    {
        $this->producer = $producer;
        $this->mapper = $mapper;
    }

    public function addContact(GenericEvent $event)
    {
        $customer = $event->getSubject();
        /** @var Customer $customer */
        Assert::isInstanceOf($customer, Customer::class);

        $sObject = $this->mapper->mapToSalesforceObject($customer);

        $this->producer->publish(serialize([
            'sObject' => $sObject,
            'class' => Customer::class,
            'id' => $customer->getId()
        ]));
    }

    public function updateContact(GenericEvent $event)
    {
        $customer = $event->getSubject();
        /** @var Customer $customer */
        Assert::isInstanceOf($customer, Customer::class);

        $sObject = $this->mapper->mapToSalesforceObject($customer);

        $this->producer->publish(serialize([
            'sObject' => $sObject,
            'class' => Customer::class,
            'id' => $customer->getId()
        ]));
    }
}