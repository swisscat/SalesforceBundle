<?php

namespace Swisscat\SalesforceBundle\Test\Producer;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\Mapping\ClassMetadata;
use Swisscat\SalesforceBundle\Test\TestData\Customer;

class TestCase extends \Swisscat\SalesforceBundle\Test\TestCase
{
    /**
     * @param array $customerData
     * @return array|Customer
     */
    public static function createTestCustomer(array $customerData = [])
    {
        $defaults = [
            'id' => 10,
            'firstName' => 'First',
            'lastName' => 'Last',
            'email' => 'customer@test.com'
        ];

        $customerData = array_replace($defaults, $customerData);

        $customer = new Customer();

        $refl = new \ReflectionClass($customer);

        foreach ($customerData as $prop => $value) {
            $prop = $refl->getProperty($prop);
            $prop->setAccessible(true);
            $prop->setValue($customer, $value);
        }

        return $customer;
    }

    /**
     * @return ClassMetadata
     */
    public static function getCustomerMetadata()
    {
        $meta = new ClassMetadata(Customer::class);
        $meta->mapField(['fieldName' => 'firstName']);
        $meta->mapField(['fieldName' => 'lastName']);
        $meta->mapField(['fieldName' => 'email']);
        $meta->mapField(['fieldName' => 'id']);
        $meta->setIdentifier(['id']);
        $meta->wakeupReflection(new RuntimeReflectionService());

        return $meta;
    }
}