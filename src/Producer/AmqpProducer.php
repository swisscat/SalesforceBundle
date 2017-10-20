<?php

namespace Swisscat\SalesforceBundle\Producer;

use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface as AmqpProducerInterface;
use Swisscat\SalesforceBundle\Mapping\Mapper;
use Swisscat\SalesforceBundle\Mapping\MappingException;

class AmqpProducer implements ProducerInterface
{
    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var AmqpProducerInterface
     */
    private $amqpProducer;

    /**
     * @param Mapper $mapper
     * @param AmqpProducerInterface $amqpProducer
     */
    public function __construct(Mapper $mapper, AmqpProducerInterface $amqpProducer)
    {
        $this->mapper = $mapper;
        $this->amqpProducer = $amqpProducer;
    }

    /**
     * @inheritdoc
     * @throws ProducerException
     */
    public function publish($object, array $context = []): void
    {
        try {
            $mappedObject = $this->mapper->mapToSalesforceObject($object, $context['action']);
        } catch (MappingException $e) {
            throw ProducerException::fromMappingException($e);
        }

        try {
            $this->amqpProducer->publish(json_encode($mappedObject));
        } catch (\Throwable $e) {
            throw ProducerException::fromAmqpPublishException($e);
        }
    }
}