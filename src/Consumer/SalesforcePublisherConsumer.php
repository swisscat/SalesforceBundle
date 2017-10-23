<?php

namespace Swisscat\SalesforceBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\BatchConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Phpforce\SoapClient\Result\SaveResult;
use Psr\Log\LoggerInterface;
use Phpforce\SoapClient\BulkSaver;
use Swisscat\SalesforceBundle\Mapping\Action;
use Swisscat\SalesforceBundle\Mapping\Mapper;
use Swisscat\SalesforceBundle\Mapping\Salesforce\SyncEvent;

class SalesforcePublisherConsumer implements BatchConsumerInterface
{
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
     * @param Mapper $mapper
     * @param LoggerInterface $logger
     * @param BulkSaver $bulkSaver
     */
    public function __construct(Mapper $mapper, LoggerInterface $logger, BulkSaver $bulkSaver)
    {
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

            foreach ($metadata->getIdentificationStrategies() as $identificationStrategy) {
                $identificationStrategy->persistSalesforceAction($syncEvent->getSObject(), $saveResult->getId(), $syncEvent->getAction());
            }
        }

        return $response;
    }
}