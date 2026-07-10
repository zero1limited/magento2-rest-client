<?php

namespace Magento2API\Service;

use Magento2API\ClientInterface;

class Stock extends AbstractService
{
    /** @var ClientInterface */
    protected $client;

    public function __construct(
        ClientInterface $client
    ){
        $this->client = $client;   
    }



    /**
     * Update the stock level of the given SKU.
     *
     * @param string $sku
     * @param int $quantity
     * @param int|null $item_id
     * @return mixed
     * @throws Authentication
     * @throws RequestFailed
     */
    public function setStockLevelForSku(string $sku, int $quantity, int $item_id = null)
    {
        // We can't set the default above to be 1, so null-check
        // it here and then default to item 1 in the product.
        if ($item_id === null) {
            $item_id = 1;
        }

        $response = $this->getClient()->request(
            'PUT',
            $this->baseUrl . '/rest/V1/products/' . $sku . '/stockItems/' . $item_id,
            [
                'json' => [
                    'stockItem' => [
                        'qty' => $quantity
                    ]
                ]
            ]
        );

        return $this->handleResponse($response);
    }


    /**
     * Update the stock level of the given SKU.
     *
     * @param string $sku
     * @param $stockData
     * @param int|null $item_id
     * @return mixed
     * @throws Authentication
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setStockDataForSku(string $sku, $stockData, int $item_id = null)
    {
        // We can't set the default above to be 1, so null-check
        // it here and then default to item 1 in the product.
        if ($item_id === null) {
            $item_id = 1;
        }

        return $this->request(
            'PUT',
            $this->baseUrl . '/rest/V1/products/' . $sku . '/stockItems/' . $item_id,
            [
                'json' => [
                    'stockItem' => $stockData
                ]
            ]
        );
    }

    /**
     * @param $sku
     * @return mixed
     * @throws Authentication
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @see https://devdocs.magento.com/swagger/index.html#/catalogInventoryStockRegistryV1/catalogInventoryStockRegistryV1GetStockItemBySkuGet
     */
    public function getStockStatuses($sku)
    {
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest/V1/stockStatuses/' . $sku
        );

        return $this->handleResponse($response);
    }
    

    /**
     * @param $sku
     * @return mixed
     * @throws Authentication
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @see https://devdocs.magento.com/swagger/index.html#/catalogInventoryStockRegistryV1/catalogInventoryStockRegistryV1GetStockItemBySkuGet
     */
    public function getStockItem($sku)
    {
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest/V1/stockItems/' . $sku
        );

        return $this->handleResponse($response);
    }
}