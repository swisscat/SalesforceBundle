<?php

namespace Swisscat\SalesforceBundle\Test\Command;

use Doctrine\ORM\EntityManagerInterface;
use Swisscat\SalesforceBundle\Command\SalesforceSoqlConfigCommand;
use Swisscat\SalesforceBundle\Command\SalesforceTopicConfigCommand;
use Swisscat\SalesforceBundle\Mapping\Driver\XmlDriver;
use Swisscat\SalesforceBundle\Test\TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;

class SalesforceTopicConfigTest extends TestCase
{
    public function testConfigDumpCommandWorksAsExpected()
    {
        $input = new ArgvInput();

        $driver = new XmlDriver([dirname(__DIR__).'/TestData/local_mapping_property']);
        $driver->setEntityManager($this->createMock(EntityManagerInterface::class));

        $cmd = $this->getMockBuilder(SalesforceTopicConfigCommand::class)
            ->setMethods(['getContainer'])
            ->getMock();

        $containerMock = $this->createMock(Container::class);

        $cmd->expects($this->once())
            ->method('getContainer')
            ->willReturn($containerMock);

        $containerMock->expects($this->once())
            ->method('get')
            ->with('salesforce.mapping.driver')
            ->willReturn($driver);

        $output = $this->createMock(OutputInterface::class);

        $output->expects($this->once())
            ->method('write')
            ->with(<<<EOD
salesforce:
    streams:
        - { type: topic, name: ContactTopic, resource: Swisscat\SalesforceBundle\Test\TestData\Customer }

EOD
);

        $cmd->run($input, $output);
    }

    public function testSoqlConfigDump()
    {
        $input = new ArgvInput();

        $driver = new XmlDriver([dirname(__DIR__).'/TestData/local_mapping_property']);
        $driver->setEntityManager($this->createMock(EntityManagerInterface::class));

        $cmd = $this->getMockBuilder(SalesforceSoqlConfigCommand::class)
            ->setMethods(['getContainer'])
            ->getMock();

        $containerMock = $this->createMock(Container::class);

        $cmd->expects($this->once())
            ->method('getContainer')
            ->willReturn($containerMock);

        $containerMock->expects($this->once())
            ->method('get')
            ->with('salesforce.mapping.driver')
            ->willReturn($driver);

        $output = $this->createMock(OutputInterface::class);

        $output->expects($this->once())
            ->method('write')
            ->with(<<<EOD
PushTopic pushTopic = new PushTopic();
pushTopic.Name = 'ContactTopic';
pushTopic.Query = 'SELECT Id, SystemModStamp, FirstName, LastName, Email, Name FROM Contact';
pushTopic.ApiVersion = 40.0;
pushTopic.NotifyForOperationCreate = true;
pushTopic.NotifyForOperationUpdate = true;
pushTopic.NotifyForOperationUndelete = true;
pushTopic.NotifyForOperationDelete = true;
pushTopic.NotifyForFields = 'Referenced';
insert pushTopic;
EOD
            );

        $cmd->run($input, $output);
    }

    public function testSoqlConfigDumpWithDelete()
    {
        $driver = new XmlDriver([dirname(__DIR__).'/TestData/local_mapping_property']);
        $driver->setEntityManager($this->createMock(EntityManagerInterface::class));

        $cmd = $this->getMockBuilder(SalesforceSoqlConfigCommand::class)
            ->setMethods(['getContainer'])
            ->getMock();

        $containerMock = $this->createMock(Container::class);

        $cmd->expects($this->once())
            ->method('getContainer')
            ->willReturn($containerMock);

        $containerMock->expects($this->once())
            ->method('get')
            ->with('salesforce.mapping.driver')
            ->willReturn($driver);

        $output = $this->createMock(OutputInterface::class);

        $output->expects($this->once())
            ->method('write')
            ->with(<<<EOD
List<PushTopic> pts = [SELECT Id FROM PushTopic];
Database.delete(pts);
PushTopic pushTopic = new PushTopic();
pushTopic.Name = 'ContactTopic';
pushTopic.Query = 'SELECT Id, SystemModStamp, FirstName, LastName, Email, Name FROM Contact';
pushTopic.ApiVersion = 40.0;
pushTopic.NotifyForOperationCreate = true;
pushTopic.NotifyForOperationUpdate = true;
pushTopic.NotifyForOperationUndelete = true;
pushTopic.NotifyForOperationDelete = true;
pushTopic.NotifyForFields = 'Referenced';
insert pushTopic;
EOD
            );

        $input = new ArgvInput(['bin/console', '--with-delete'], $cmd->getDefinition());
        $cmd->run($input, $output);
    }
}