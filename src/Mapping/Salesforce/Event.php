<?php

namespace Swisscat\SalesforceBundle\Mapping\Salesforce;

use DateTime;
use Webmozart\Assert\Assert;

/**
 * Class Event
 * @package Swisscat\SalesforceBundle\Mapping\Salesforce
 *
 * Maps an event with the following format:
{
    "clientId":"2t80j2hcog29sdh9ihjd9643a",
    "data": {
        "event": {
            "createdDate":"2016-03-29T16:40:08.208Z",
            "replayId":13,
            "type":"created"
        },
        "sobject":{
            "Website":null,
            "Id":"001D000000KnaXjIAJ",
            "Name":"TicTacToe"
        }
    },
    "channel":"/topic/TestAccountStreaming"
}
 */

class Event
{
    const DateFormat = "Y-m-d\TH:i:s\.uO";

    /**
     * @var mixed
     */
    private $sObject;

    /**
     * @var string
     */
    private $channel;

    /**
     * @var string
     */
    private $type;

    /**
     * @var int
     */
    private $Id;

    /**
     * @var DateTime
     */
    private $createdDate;

    /**
     * @param mixed $sObject
     * @param string $channel
     * @param string $type
     * @param int $Id
     * @param DateTime $createdDate
     */
    private function __construct($sObject, $channel, $type, $Id, DateTime $createdDate)
    {
        $this->sObject = $sObject;
        $this->channel = $channel;
        $this->type = $type;
        $this->Id = $Id;
        $this->createdDate = $createdDate;
    }

    /**
     * @param array $array
     * @return Event
     */
    public static function fromArray(array $array)
    {
        Assert::keyExists($array, 'channel');
        Assert::keyExists($array, 'data');
        Assert::isArray($array['data']);
        Assert::keyExists($array['data'], 'sobject');
        Assert::keyExists($array['data'], 'event');
        Assert::isArray($array['data']['event']);
        Assert::keyExists($array['data']['event'], 'type');
        Assert::keyExists($array['data']['event'], 'replayId');
        Assert::keyExists($array['data']['event'], 'createdDate');
        return new self((object)$array['data']['sobject'], $array['channel'], $array['data']['event']['type'], $array['data']['event']['replayId'], DateTime::createFromFormat(self::DateFormat,$array['data']['event']['createdDate']));
    }

    /**
     * @return mixed
     */
    public function getSObject()
    {
        return $this->sObject;
    }

    /**
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->Id;
    }

    /**
     * @return DateTime
     */
    public function getCreatedDate(): DateTime
    {
        return $this->createdDate;
    }
}