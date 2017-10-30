<?php

namespace Swisscat\SalesforceBundle\Command;

use Swisscat\SalesforceBundle\Configuration\Dumper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SalesforceTopicConfigCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('salesforce:topic-config')
            ->setDescription('Dumps topic configuration necessary to sync defined entities')
            ;

        parent::configure();
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $configDumper = new Dumper($this->getContainer()->get('salesforce.mapping.driver'));

        $output->write($configDumper->dumpTopicConfiguration());
    }
}