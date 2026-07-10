<?php

namespace Magento2API\Service;

use Magento2API\ClientInterface;

class Store extends AbstractService
{
    /** @var ClientInterface */
    protected $client;

    public function __construct(
        ClientInterface $client
    ){
        $this->client = $client;   
    }


    

    /**
     * @param array $stores
     * @return mixed
     * @throws Authentication
     * @throws RequestFailed
     */
    public function getStoreConfiguration($stores = [])
    {
        $query = '';
        if (!empty($stores)) {
            $query = '?' . $this->buildQueryArray('storeCode', $stores);
        }
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest/V1/store/storeConfigs' . $query
        );

        return $this->handleResponse($response);
    }

    /**
     * @return mixed
     * @throws Authentication
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @see https://devdocs.magento.com/swagger/index.html#/storeStoreRepositoryV1
     */
    public function getStoreViews()
    {
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest//V1/store/storeViews'
        );

        return $this->handleResponse($response);
    }

    /**
     * @return mixed
     * @throws Authentication
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @see https://devdocs.magento.com/swagger/index.html#/storeGroupRepositoryV1
     */
    public function getStoreGroups()
    {
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest/V1/store/storeGroups'
        );

        return $this->handleResponse($response);
    }
}