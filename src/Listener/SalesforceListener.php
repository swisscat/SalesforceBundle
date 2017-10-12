<?php

namespace Swisscat\SalesforceBundle\Listener;

use Swisscat\SalesforceBundle\Producer\ProducerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

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

    public function publishEventSubject(GenericEvent $event)
    {
        $this->producer->publish($event->getSubject());
    }
}