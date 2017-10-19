<?php

namespace Swisscat\SalesforceBundle\Consumer;

use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\BatchConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Phpforce\SoapClient\Result\SaveResult;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Swisscat\SalesforceBundle\Entity\SalesforceMapping;
use Phpforce\SoapClient\BulkSaver;
use Swisscat\SalesforceBundle\Mapping\Action;
use Swisscat\SalesforceBundle\Mapping\Mapper;
use Swisscat\SalesforceBundle\Mapping\Salesforce\SyncEvent;

class SalesforcePublisherConsumer implements BatchConsumerInterface
{
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
     * @var BulkSaver
     */
    private $bulkSaver;

    /**
     * SalesforcePublisherConsumer constructor.
     * @param EntityManagerInterface $entityManager
     * @param Mapper $mapper
     * @param LoggerInterface $logger
     * @param BulkSaver $bulkSaver
     */
    public function __construct(EntityManagerInterface $entityManager, Mapper $mapper, LoggerInterface $logger, BulkSaver $bulkSaver)
    {
        $this->entityManager = $entityManager;
        $this->mapper = $mapper;
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
        $deletes = [];

        foreach ($messages as $key => $message) {
            if (null === $body = json_decode($message->body, true)) {
                $this->logger->info('Invalid message content');
                $this->logger->debug('Message body: '. $message->body);
                $messageMetadata[$key]['valid'] = false;
                continue;
            }

            $messageMetadata[$key]['valid'] = true;

            $syncEvent = SyncEvent::fromArray($body);

            $sObject = $syncEvent->getSObject();
            $metadata = $this->mapper->loadMetadataForClass($syncEvent->getLocalType());
            $action = $syncEvent->getAction();

            $matchField = null;
            switch ($action) {
                case Action::Create:
                case Action::Update:
                    if ($metadata->hasExternalIdMapping()) {
                        $upserts[] = $key;
                        $matchField = $metadata->getExternalIdMapping();
                    } else {
                        if ($sObject->id) {
                            $updates[] = $key;
                        } else {
                            $creates[] = $key;
                        }
                    }
                    break;

                case Action::Delete:
                    $deletes[] = $key;
            }

            $this->bulkSaver->save($sObject, $syncEvent->getSObjectType(), $matchField);
        }

        $bulkResult = $this->bulkSaver->flush();

        foreach ([$creates, $updates, $upserts, $deletes] as $array) {
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

            $syncEvent = SyncEvent::fromArray(json_decode($message->body,true));

            $metadata = $this->mapper->loadMetadataForClass($syncEvent->getLocalType());

            $response[$key] = $saveResult->isSuccess();

            if ($localMapping = $metadata->getSalesforceIdLocalMapping()) {
                switch ($localMapping['type']) {
                    case 'mappingTable':
                        $mapping = $this->entityManager->getRepository(SalesforceMapping::class)->findOneBy(['entityType' => $syncEvent->getLocalType(), 'entityId' => $syncEvent->getLocalId()]);
                        switch ($syncEvent->getAction()) {
                            case Action::Update:
                                if (!$mapping) {
                                    $this->logger->log(LogLevel::ERROR, "Invalid mapping state on update");
                                }
                                break;

                            case Action::Create:
                                if (!$mapping) {
                                    $mapping = new SalesforceMapping();
                                    $mapping->setEntityType($syncEvent->getLocalType());
                                    $mapping->setEntityId($syncEvent->getLocalId());
                                }

                                $mapping->setSalesforceId($saveResult->getId());

                                $this->entityManager->persist($mapping);
                                break;

                            case Action::Delete:
                                if (!$mapping) {
                                    $this->logger->log(LogLevel::ERROR, "Invalid mapping state on delete");
                                }

                                $this->entityManager->remove($mapping);
                                break;
                        }

                        break;

                    case 'property':
                        $entity = $this->entityManager->getRepository($syncEvent->getLocalType())->find($syncEvent->getLocalId());

                        switch ($syncEvent->getAction()) {
                            case Action::Update:
                                break;

                            case Action::Create:
                                $doctrineMetadata = $this->entityManager->getClassMetadata($entity);

                                $doctrineMetadata->reflFields[$localMapping['property']]->setValue($entity, $saveResult->getId());

                                $this->entityManager->persist($entity);
                                break;

                            case Action::Delete:
                                $this->entityManager->remove($entity);
                                break;
                        }
                        break;
                }
            }
        }

        $this->entityManager->flush();

        return $response;
    }
}