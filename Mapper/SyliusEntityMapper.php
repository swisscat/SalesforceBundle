<?php

namespace Swisscat\SalesforceBundle\Mapper;

use Ddeboer\Salesforce\MapperBundle\Model\Contact;
use Sylius\Component\Core\Model\Customer;

class SyliusEntityMapper
{
    /**
     * @param Customer $customer
     * @return Contact
     */
    public function getContactFromCustomer(Customer $customer)
    {
        $contact = new Contact();
        $contact->setFirstName($customer->getFirstName());
        $contact->setLastName($customer->getLastName());
        $contact->setEmail($customer->getEmail());

        return $contact;
    }
}