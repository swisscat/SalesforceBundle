<?php

namespace Swisscat\SalesforceBundle\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class RefreshWsdlCommand
 * @package Swisscat\SalesforceBundle\Command
 */
class RefreshWsdlCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('salesforce:refresh-wsdl')
            ->setDescription('Refresh Salesforce WSDL')
            ->setHelp(
                'Refreshing the WSDL itself requires a WSDL, so when using this'
                . 'command for the first time, please download the WSDL '
                . 'manually from Salesforce'
            )
            ->addOption(
                'no-cache-clear',
                'c',
                InputOption::VALUE_NONE,
                'Do not clear cache after refreshing WSDL'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Updating the WSDL file');

        $client = $this->getContainer()->get('salesforce.soap_client');

        // Get current session id
        $loginResult = $client->getLoginResult();
        $sessionId = $loginResult->getSessionId();
        $instance = $loginResult->getServerInstance();

        $url = sprintf('https://%s.salesforce.com', $instance);
        $guzzle = new Client();

        $cookieJar = CookieJar::fromArray([
            'sid' => $sessionId
        ], 'salesforce.com');

        $response = $guzzle->request('GET', $url.'/soap/wsdl.jsp?type=*', [
            'cookies' => $cookieJar,
            'verify' => false,
        ]);

        $wsdl = $response->getBody()->getContents();
        $wsdlFile = $this->getContainer()
            ->getParameter('salesforce.soap_client.wsdl');

        // Write WSDL
        file_put_contents($wsdlFile, $wsdl);

        // Run clear cache command
        if (!$input->getOption('no-cache-clear')) {
            $command = $this->getApplication()->find('cache:clear');

            $arguments = array(
                'command' => 'cache:clear'
            );
            $input = new ArrayInput($arguments);
            $command->run($input, $output);
        }
    }
}

