<?php

namespace Magento2API\Service;

use Magento2API\ClientInterface;

class Products extends AbstractService
{
    /** @var ClientInterface */
    protected $client;

    public function __construct(
        ClientInterface $client
    ){
        $this->client = $client;   
    }


    

    /**
     * @param string $sku
     * @parem array $tierPrices
     * @return mixed
     *
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @see https://devdocs.magento.com/swagger/index.html
     */
    public function setTierPrices($sku, $tierPrices)
    {
        // force everything to a sku
        foreach($tierPrices as &$tierPrice){
            $tierPrice['sku'] = $sku;
        }

        $response = $this->getClient()->request(
            'PUT',
            $this->baseUrl . '/rest/V1/products/tier-prices',
            [
                'json' => [
                    'prices' => $tierPrices
                ]
            ]
        );

        return $this->handleResponse($response);
    }

    /**
     * @param string $sku
     * @return mixed
     *
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @see https://devdocs.magento.com/swagger/index.html
     */
    public function getTierPrices($sku)
    {
        $response = $this->getClient()->request(
            'POST',
            $this->baseUrl . '/rest/V1/products/tier-prices-information',
            [
                'json' => [
                    'skus' => [$sku]
                ]
            ]
        );
        return $this->handleResponse($response);
    }

    /**
     * @param string $sku
     * @param string|null $customerGroup - specifiy to only remove for a specific customer group
     * @return mixed
     *
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @see https://devdocs.magento.com/swagger/index.html
     */
    public function removeTierPrices($sku)
    {
        // for magento 2 we have to retrieve them so we can remove them
        $tierPrices = $this->getTierPrices($sku);
        foreach($tierPrices as &$tierPrice){
            $tierPrice['sku'] = $sku;
        }

        $response = $this->getClient()->request(
            'POST',
            $this->baseUrl . '/rest/V1/products/tier-prices-delete',
            [
                'json' => [
                    'prices' => $tierPrices
                ]
            ]
        );
        return $this->handleResponse($response);
    }

    /**
     * Set the website a product is for.
     *
     * @param $sku
     * @param $websiteId
     * @return mixed
     * @throws Authentication
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @see http://devdocs.magento.com/swagger/#!/catalogProductWebsiteLinkRepositoryV1
     */
    public function setWebsiteForProduct($sku, $websiteId)
    {
        $response = $this->getClient()->request(
            'PUT',
            $this->baseUrl . '/rest/V1/products/'.$sku.'/websites',
            [
                'json' => [
                    'productWebsiteLink' => [
                        'sku' => $sku,
                        'website_id' => (int)$websiteId
                    ]
                ]
            ]
        );

        return $this->handleResponse($response);
    }

    /**
     * @param array $product
     * @return mixed
     *
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Magento2Api\Client\Exception\EntityNotFoundException
     * @see https://devdocs.magento.com/swagger/index.html
     */
    public function updateProduct($sku, $data = [], $storeCode = 'default')
    {
        $data['sku'] = $sku;

        return $this->request(
            'POST',
            $this->baseUrl . '/rest/'.$storeCode.'/V1/products',
            [
                'json' => [
                    "product" => $data
                ]
            ]
        );
    }

    /**
     * @param string $sku
     * @return array
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleExceptioncatalogProductRepositoryV1
     * @throws \Magento2Api\Client\Exception\EntityNotFoundException
     * @see https://devdocs.magento.com/swagger/#/catalogProductRepositoryV1/catalogProductRepositoryV1GetGet
     */
    public function getProductBySku($sku)
    {
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest/V1/products/' . $sku
        );

        return $this->handleResponse($response);
    }

    /**
     * @param array $product
     * @return mixed
     *
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @see https://devdocs.magento.com/swagger/index.html
     */
    public function createProduct($product)
    {
        $response = $this->getClient()->request(
            'POST',
            $this->baseUrl . '/rest/default/V1/products',
            [
                'json' => [
                    "product" => $product
                ]
            ]
        );

        return $this->handleResponse($response);
    }

    /**
     * @param array $where
     * @param null|array $orderBy
     * @param int $page
     * @param int $limit
     * @param array $fieldsFilters As list of the fields you want back, eg: ['items[sku]','total_count']
     * @return array
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @see https://devdocs.magento.com/swagger/index.html#/catalogProductRepositoryV1/catalogProductRepositoryV1GetListGet
     */
    public function getProducts($where = [], $orderBy = null, $page = 1, $limit = 100, $fieldsFilters = [])
    {
        $searchCriteria = $this->buildQuery($where, $orderBy, $page, $limit, $fieldsFilters);
        return $this->request(
            'GET',
            $this->baseUrl . '/rest/V1/products?' . $searchCriteria->toString()
        );
    }

    /**
     * recursively calls ->getProducts() getting all results not just the first page
     *
     * @param array $where
     * @param null $orderBy
     * @param int $limit
     * @param array $fieldsFilters As list of the fields you want back, eg: ['items[sku]','total_count']
     * @return array
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAllProducts($where = [], $orderBy = null, $limit = 100, $fieldsFilters = [])
    {
        if($orderBy === null){
            $orderBy = [
                'field' => 'entity_id',
                'direction' => 'asc',
            ];
        }

        if(!empty($fieldsFilters) && !in_array('total_count', $fieldsFilters)){
            $fieldsFilters[] = 'total_count';
        }

        $aggregatedResponse = [
            'items' => [],
            'total_count' => null,
        ];
        $currentPage = 1;

        while($aggregatedResponse['total_count'] === null || $currentPage * $limit < $aggregatedResponse['total_count']){

            $response = $this->getProducts($where, $orderBy, $currentPage, $limit, $fieldsFilters);

            array_push($aggregatedResponse['items'], ...$response['items']);
            $aggregatedResponse['total_count'] = $response['total_count'];

            // echo date('c').' '.count($aggregatedResponse['items']).' / '.$aggregatedResponse['total_count'].PHP_EOL;
            $currentPage++;
        }

        return $aggregatedResponse;
    }
}