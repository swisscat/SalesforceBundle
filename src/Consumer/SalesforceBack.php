<?php

namespace Swisscat\SalesforceBundle\Consumer;

use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Swisscat\SalesforceBundle\Mapping\Driver\DriverInterface;
use Swisscat\SalesforceBundle\Mapping\Mapper;
use Webmozart\Assert\Assert;

class SalesforceBack implements ConsumerInterface
{
    /**
     * @var array
     */
    private $streamConfiguration;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param array $streamConfiguration
     * @param EntityManagerInterface $entityManager
     * @param DriverInterface $driver
     * @param LoggerInterface $logger
     */
    public function __construct(array $streamConfiguration, EntityManagerInterface $entityManager, DriverInterface $driver, LoggerInterface $logger)
    {
        $this->streamConfiguration = $streamConfiguration;
        $this->entityManager = $entityManager;
        $this->mapper = new Mapper($driver, $this->entityManager);
        $this->logger = $logger;
    }

    private function findTopic(string $topicName)
    {
        foreach ($this->streamConfiguration as $configuration) {
            if ($configuration['type'] === 'topic' && $configuration['name'] === $topicName) {
                return $configuration;
            }
        }

        throw new \InvalidArgumentException(sprintf('Topic not found in configuration: %s', $topicName));
    }

    public function execute(AMQPMessage $msg)
    {
        if (null === $body = json_decode($msg->body, true)) {
            $this->logger->log(LogLevel::INFO, 'Invalid JSON');
            return false;
        }

        Assert::keyExists($body, 'event');
        $eventMetadata = $body['event'];
        Assert::keyExists($eventMetadata, 'stream');

        // Stream topic
        if (strpos($eventMetadata['stream'], '/topic/') === 0) {
            $config = $this->findTopic(substr($eventMetadata['stream'], 7));
        } else {
            throw new \InvalidArgumentException('Unsupported');
        }

        Assert::keyExists($body, 'sobject');
        Assert::keyExists($body['sobject'], 'Id');
        $salesforceId = $body['sobject']["Id"];
        $entityType = $config['resource'];

        $entity = $this->mapper->getEntity($entityType, $salesforceId);

        if ($entity === null) {
            $this->logger->log(LogLevel::INFO, 'No local storage');
            return true;
        }

        $this->mapper->mapFromSalesforceObject((object)$body['sobject'], $entity);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}