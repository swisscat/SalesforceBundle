<?php

namespace Swisscat\SalesforceBundle\Consumer;

use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\BatchConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Phpforce\SoapClient\Result\SaveResult;
use Psr\Log\LoggerInterface;
use Swisscat\SalesforceBundle\Entity\SalesforceMapping;
use Swisscat\SalesforceBundle\Mapping\BulkSaver;
use Swisscat\SalesforceBundle\Mapping\Driver\DriverInterface;
use Swisscat\SalesforceBundle\Mapping\Salesforce\MappedObject;

class SalesforcePublisherConsumer implements BatchConsumerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DriverInterface
     */
    private $mappingDriver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var BulkSaver
     */
    private $bulkSaver;

    /**
     * SalesforcePublisherConsumer constructor.
     * @param EntityManagerInterface $entityManager
     * @param DriverInterface $mappingDriver
     * @param LoggerInterface $logger
     * @param BulkSaver $bulkSaver
     */
    public function __construct(EntityManagerInterface $entityManager, DriverInterface $mappingDriver, LoggerInterface $logger, BulkSaver $bulkSaver)
    {
        $this->entityManager = $entityManager;
        $this->mappingDriver = $mappingDriver;
        $this->logger = $logger;
        $this->bulkSaver = $bulkSaver;
    }

    /**
     * @param   AMQPMessage[]   $messages
     *
     * @return  array|bool
     */
    public function batchExecute(array $messages)
    {
        $messageMetadata = [];
        $creates = [];
        $updates = [];
        $upserts = [];
        foreach ($messages as $key => $message) {
            if (false === $body = json_decode($message->body, true)) {
                $this->logger->info('Invalid message content');
                $this->logger->debug('Message body: '. $message->body);
                continue;
            }

            $mappedObject = MappedObject::fromArray($body);

            $metadata = $this->mappingDriver->loadMetadataForClass($mappedObject->getLocalType());


            $matchField = null;

            if ($metadata->hasSalesforceMapping()) {
                $upserts[] = $key;
                $matchField = $metadata->getSalesforceIdentifier();
            } else {
                $mapping = $this->entityManager->getRepository(SalesforceMapping::class)->findOneBy(['entityType' => $body['class'], 'entityId' => $body['id']]);
                if (!$mapping) {
                    $mapping = new SalesforceMapping();
                    $mapping->setEntityType($body['class']);
                    $mapping->setEntityId($body['id']);
                } else {
                    $body['sObject']->id = $mapping->getSalesforceId();
                }

                $messageMetadata[$key]['mapping'] = $mapping;
                if ($body['sObject']->id) {
                    $updates[] = $key;
                } else {
                    $creates[] = $key;
                }
            }

            $this->bulkSaver->save($body['sObject'], 'Contact', $matchField);
        }

        $bulkResult = $this->bulkSaver->flush();

        foreach ([$creates, $updates, $upserts] as $array) {
            if ($array) {
                foreach ($bulkResult[0] as $key => $result) {
                    $messageMetadata[$array[$key]]['result'] = $result;
                }

                array_shift($bulkResult);
            }
        }

        $response = [];

        foreach ($messages as $key => $message) {
            /** @var SaveResult $saveResult */
            $saveResult = $messageMetadata[$key]['result'];
            /** @var SalesforceMapping $mapping */
            $mapping = $messageMetadata[$key]['mapping'];

            $response[$key] = $saveResult->isSuccess();

            if ($messageMetadata[$key]['config']->hasClientMapping()) {
                if (!$mapping->getSalesforceId()) {
                    $mapping->setSalesforceId($saveResult->getId());
                    $this->entityManager->persist($mapping);
                }
            }
        }

        $this->entityManager->flush();

        return $response;
    }
}