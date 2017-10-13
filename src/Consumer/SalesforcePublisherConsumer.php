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
            if (null === $body = json_decode($message->body, true)) {
                $this->logger->info('Invalid message content');
                $this->logger->debug('Message body: '. $message->body);
                $messageMetadata[$key]['valid'] = false;
                continue;
            }

            $messageMetadata[$key]['valid'] = true;

            $body['sObject'] = (object)$body['sObject'];

            $mappedObject = MappedObject::fromArray($body);

            $messageMetadata[$key]['metadata'] = $metadata = $this->mappingDriver->loadMetadataForClass($mappedObject->getLocalType());

            $matchField = null;

            $sObject = $mappedObject->getSObject();

            if ($metadata->hasExternalIdMapping()) {
                $upserts[] = $key;
                $matchField = $metadata->getExternalIdMapping();
            } else {
                $localMapping = $metadata->getSalesforceIdLocalMapping();

                switch ($localMapping['type']) {
                    case 'mappingTable':
                        $mapping = $this->entityManager->getRepository(SalesforceMapping::class)->findOneBy(['entityType' => $mappedObject->getLocalType(), 'entityId' => $mappedObject->getLocalId()]);
                        if (!$mapping) {
                            $mapping = new SalesforceMapping();
                            $mapping->setEntityType($mappedObject->getLocalType());
                            $mapping->setEntityId($mappedObject->getLocalId());
                        } else {
                            $sObject->id = $mapping->getSalesforceId();
                        }

                        $messageMetadata[$key]['mapping'] = $mapping;
                        break;

                    case 'property':
                        $entity = $this->entityManager->getRepository($mappedObject->getLocalType())->find($mappedObject->getLocalId());

                        if ($id = $entity->{'get'.ucfirst($localMapping['property'])}()) {
                            $sObject->id = $id;
                        }

                        $messageMetadata[$key]['entity'] = $entity;
                        break;
                }

                if ($sObject->id) {
                    $updates[] = $key;
                } else {
                    $creates[] = $key;
                }
            }

            $this->bulkSaver->save($sObject, $mappedObject->getSalesforceType(), $matchField);
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
            if (!$messageMetadata[$key]['valid']) {
                $response[$key] = true;
                continue;
            }
            /** @var SaveResult $saveResult */
            $saveResult = $messageMetadata[$key]['result'];
            /** @var SalesforceMapping $mapping */
            $mapping = $messageMetadata[$key]['mapping'];

            $response[$key] = $saveResult->isSuccess();

            if ($localMapping = $messageMetadata[$key]['metadata']->getSalesforceIdLocalMapping()) {
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