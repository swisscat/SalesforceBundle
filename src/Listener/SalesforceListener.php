<?php

namespace Swisscat\SalesforceBundle\Listener;

use Swisscat\SalesforceBundle\Mapping\Action;
use Swisscat\SalesforceBundle\Producer\ProducerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class SalesforceListener
{
    const Action = 'action';

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @param ProducerInterface $producer
     */
    public function __construct(ProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    /**
     * @param GenericEvent $event
     */
    public function publishCreateEvent(GenericEvent $event): void
    {
        $this->publishEventSubject($event, [self::Action => Action::Create]);
    }

    /**
     * @param GenericEvent $event
     */
    public function publishUpdateEvent(GenericEvent $event): void
    {
        $this->publishEventSubject($event, [self::Action => Action::Update]);
    }

    /**
     * @param GenericEvent $event
     */
    public function publishDeleteEvent(GenericEvent $event): void
    {
        $this->publishEventSubject($event, [self::Action => Action::Delete]);
    }

    /**
     * @param GenericEvent $event
     * @param array $context
     */
    private function publishEventSubject(GenericEvent $event, array $context = []): void
    {
        $this->producer->publish($event->getSubject(), $context);
    }
}