<?php

namespace Swisscat\SalesforceMapperBundle\Consumer;

use Ddeboer\Salesforce\MapperBundle\MappedBulkSaverInterface;
use OldSound\RabbitMqBundle\RabbitMq\BatchConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class SalesforcePublisherConsumer implements BatchConsumerInterface
{
    /**
     * @var MappedBulkSaverInterface
     */
    private $bulkSaver;

    public function __construct(MappedBulkSaverInterface $bulkSaver)
    {
        $this->bulkSaver = $bulkSaver;
    }

    public function batchExecute(array $messages)
    {
        array_map([$this, 'processMessage'], $messages);

        var_dump($this->bulkSaver->flush());

        return array_map(function($msg) { return 1;}, $messages);
    }

    private function processMessage(AMQPMessage $message)
    {
        if (!($body = unserialize($message->body)) || !isset($body['sObject'])) {
            return false;
        }

        $this->bulkSaver->save($body['sObject']);

        return true;
    }
}