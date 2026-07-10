<?php

namespace Magento2API\Service;

use Magento2API\ClientInterface;

class Customers extends AbstractService
{
    /** @var ClientInterface */
    protected $client;

    public function __construct(
        ClientInterface $client
    ){
        $this->client = $client;   
    }



    /**
     * @param $customer_id
     * @return mixed
     * @throws Authentication
     * @throws RequestFailed
     */
    public function getCustomer($customer_id)
    {
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest/V1/customers/' . $customer_id
        );

        return $this->handleResponse($response);
    }
    
}