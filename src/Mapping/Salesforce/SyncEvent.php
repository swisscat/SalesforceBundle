<?php

namespace Swisscat\SalesforceBundle\Mapping\Salesforce;

use Webmozart\Assert\Assert;

class SyncEvent implements \JsonSerializable
{
    /**
     * @var \stdClass
     */
    private $sObject;

    /**
     * @var string
     */
    private $sObjectType;

    /**
     * @var string
     */
    private $localId;

    /**
     * @var string
     */
    private $localType;

    /**
     * @var string
     */
    private $action;

    /**
     * @param \stdClass $sObject
     * @param string $sObjectType
     * @param string $localId
     * @param string $localType
     * @param string $action
     */
    public function __construct(\stdClass $sObject, string $sObjectType, string $localId, string $localType, string $action)
    {
        $this->sObject = $sObject;
        $this->sObjectType = $sObjectType;
        $this->localId = $localId;
        $this->localType = $localType;
        $this->action = $action;
    }

    /**
     * @return \stdClass
     */
    public function getSObject(): \stdClass
    {
        return $this->sObject;
    }

    /**
     * @return string
     */
    public function getSObjectType(): string
    {
        return $this->sObjectType;
    }

    /**
     * @return string
     */
    public function getLocalId(): string
    {
        return $this->localId;
    }

    /**
     * @return string
     */
    public function getLocalType(): string
    {
        return $this->localType;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    function jsonSerialize()
    {
        return [
            'salesforce' => [
                'sObject' =>$this->sObject,
                'type' => $this->sObjectType
            ],
            'local' => [
                'id' => $this->localId,
                'type' => $this->localType
            ],
            'action' => $this->action,
        ];
    }

    /**
     * @param array $array
     * @return SyncEvent
     */
    public static function fromArray(array $array)
    {
        Assert::keyExists($array, 'salesforce');
        Assert::keyExists($array, 'local');
        Assert::keyExists($array, 'action');
        Assert::keyExists($array['salesforce'], 'sObject');
        Assert::keyExists($array['salesforce'], 'type');
        Assert::keyExists($array['local'], 'id');
        Assert::keyExists($array['local'], 'type');

        return new self((object)$array['salesforce']['sObject'], $array['salesforce']['type'], $array['local']['id'], $array['local']['type'], $array['action']);
    }
}