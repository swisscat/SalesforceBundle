<?php

namespace Swisscat\SalesforceBundle\Test\Consumer;

class FullRemotePublisherTest extends TestCase
{
    public function testCreateCustomer()
    {
        $message = $this->generateAmqpMessage(['salesforce' => ['sObject' => ['Id' => 'sf1234', 'ExternalId__c' => 10]], 'action'=> 'create']);
        list($consumer, $saver) = $this->createConsumer('FullRemote');

        $sobject = new \stdClass();
        $sobject->fieldsToNull = [];
        $sobject->FirstName = 'First';
        $sobject->LastName = 'Last';
        $sobject->Email = 'customer@test.com';
        $sobject->Id = 'sf1234';
        $sobject->ExternalId__c = 10;

        $saver->expects($this->once())
            ->method('save')
            ->with($sobject, 'Contact', 'ExternalId__c');

        $saver->expects($this->once())
            ->method('flush')
            ->willReturn([[$this->generateSaveResult(['id' => 'sf1234', 'success' => true])]]);

        $consumer->batchExecute([$message]);
    }

    public function testUpdateCustomer()
    {
        $message = $this->generateAmqpMessage(['salesforce' => ['sObject' => ['Id' => 'sf1234', 'ExternalId__c' => 10]], 'action'=> 'update']);
        list($consumer, $saver) = $this->createConsumer('FullRemote');

        $sobject = new \stdClass();
        $sobject->fieldsToNull = [];
        $sobject->FirstName = 'First';
        $sobject->LastName = 'Last';
        $sobject->Email = 'customer@test.com';
        $sobject->Id = 'sf1234';
        $sobject->ExternalId__c = 10;

        $saver->expects($this->once())
            ->method('save')
            ->with($sobject, 'Contact', 'ExternalId__c');

        $saver->expects($this->once())
            ->method('flush')
            ->willReturn([[$this->generateSaveResult(['id' => 'sf1234', 'success' => true])]]);

        $consumer->batchExecute([$message]);
    }

    public function testUpdateCustomerCallback()
    {
        $message = $this->generateAmqpBackMessage(['event' => ['type' => 'updated']]);

        list($consumer, $em) = $this->createBackConsumer('FullRemote');

        $em->expects($this->never())
            ->method('getClassMetadata');

        $consumer->execute($message);
    }
}