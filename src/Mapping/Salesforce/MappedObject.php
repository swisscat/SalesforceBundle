<?php

namespace Swisscat\SalesforceBundle\Mapping\Salesforce;

use Webmozart\Assert\Assert;

class MappedObject implements \JsonSerializable
{
    /**
     * @var object
     */
    private $sObject;

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
    private $salesforceType;

    /**
     * @param object $sObject
     * @param string $localId
     * @param string $localType
     * @param string $salesforceType
     */
    public function __construct($sObject, $localId, $localType, $salesforceType)
    {
        $this->sObject = $sObject;
        $this->localId = $localId;
        $this->localType = $localType;
        $this->salesforceType = $salesforceType;
    }

    /**
     * @return object
     */
    public function getSObject(): object
    {
        return $this->sObject;
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
    public function getSalesforceType(): string
    {
        return $this->salesforceType;
    }

    function jsonSerialize()
    {
        return [
            'sObject' => $this->sObject,
            'localId' => $this->localId,
            'localType' => $this->localType,
            'salesforceType' => $this->salesforceType,
        ];
    }

    public static function fromArray(array $array)
    {
        Assert::keyExists($array, 'sObject');
        Assert::keyExists($array, 'localId');
        Assert::keyExists($array, 'localType');
        Assert::keyExists($array, 'salesforceType');

        return new self($array['sObject'], $array['localId'], $array['localType'], $array['salesforceType']);
    }
}