<?php

namespace Swisscat\SalesforceMapperBundle\Listener;

use Ddeboer\Salesforce\MapperBundle\Model\Contact;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Sylius\Component\Core\Model\Customer;
use Symfony\Component\EventDispatcher\GenericEvent;
use Webmozart\Assert\Assert;

class SalesforceListener
{
    /**
     * @var ProducerInterface
     */
    private $producer;

    public function __construct(ProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    public function addContact(GenericEvent $event)
    {
        $user = $event->getSubject();
        /** @var Customer $user */

        Assert::isInstanceOf($user, Customer::class);

        $contact = new Contact();
        $contact->setFirstName($user->getFirstName());
        $contact->setLastName($user->getLastName());
        $contact->setEmail($user->getEmail());

        $this->producer->publish(serialize([
            'sObject' => $contact
        ]));
    }
}