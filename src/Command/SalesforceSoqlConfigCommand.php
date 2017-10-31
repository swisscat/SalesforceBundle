<?php

namespace Swisscat\SalesforceBundle\Command;

use Swisscat\SalesforceBundle\Configuration\Dumper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SalesforceSoqlConfigCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('salesforce:soql-config')
            ->setDescription('Dumps topic configuration necessary to sync defined entities')
            ->addOption(
                'with-delete',
                null,
                InputOption::VALUE_NONE,
                'Include delete SOQL (to clean up previous topics)'
                )
            ;

        parent::configure();
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $configDumper = new Dumper($this->getContainer()->get('salesforce.mapping.driver'));

        $output->write($configDumper->dumpSoqlConfiguration($input->hasOption('with-delete')));
    }
}