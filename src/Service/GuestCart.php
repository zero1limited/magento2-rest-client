<?php

namespace Magento2API\Service;

use Magento2API\ClientInterface;

class GuestCart extends AbstractService
{
    /** @var ClientInterface */
    protected $client;

    public function __construct(
        ClientInterface $client
    ){
        $this->client = $client;   
    }

    public function setShippingInformation($cartId, $body, $storeCode = 'default')
    {
        $response = $this->getClient()->request(
            'POST',
            $this->baseUrl.'/rest/V1/guest-carts/'.$cartId.'/shipping-information?storeCode='.$storeCode,
            [
                'json' => $body
            ]
        );

        return $this->handleResponse($response);
    }

    public function getPaymentMethods($cartId)
    {
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl.'/rest/V1/guest-carts/'.$cartId.'/payment-methods?storeCode=db_fr',
            [
                'json' => []
            ]
        );

        return $this->handleResponse($response);
    }
}