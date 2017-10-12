<?php

namespace Swisscat\SalesforceBundle\Consumer;

use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Swisscat\SalesforceBundle\Entity\SalesforceMapping;
use Sylius\Component\Core\Model\Customer;

class SalesforceBack implements ConsumerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function execute(AMQPMessage $msg)
    {
        if (false === $body = json_decode($msg->body, true)) {
            $this->logger->log(LogLevel::INFO, 'Invalid JSON');
            return false;
        }

        $salesforceId = $body['sobject']["Id"];
        $entityType = Customer::class;

        $salesforceMapping = $this->entityManager->getRepository(SalesforceMapping::class)->findOneBy(compact('salesforceId', 'entityType'));

        if ($salesforceMapping) {
            $entity = $this->entityManager->getRepository($entityType)->find($salesforceMapping->getEntityId());

            $entity->setLastName($body['sobject']['Name']);

            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();
    }
}