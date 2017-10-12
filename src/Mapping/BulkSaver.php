<?php

namespace Swisscat\SalesforceBundle\Mapping;

use Phpforce\SoapClient\BulkSaver as BaseBulkSaver;
use Phpforce\SoapClient\ClientInterface;

class BulkSaver extends BaseBulkSaver
{
    /**
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {

        parent::__construct($client);
    }

    public function saveRecord($record)
    {
        $record = $this->mapRecord($record);

        $this->save($record, get_class($record));
    }

    private function mapRecord($record)
    {

        return $record;
    }
}

