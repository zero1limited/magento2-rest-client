<?php

namespace Magento2;

use Dsheiko\SearchCriteria;
use Magento2\Exception\Authentication;
use Magento2\Exception\InvalidArgument;
use Magento2\Exception\RequestFailed;

/**
 * Class Client
 * @package Magento2
 */
class Client
{
    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $commonHeaders = [
        'Content-Type' => 'application/json',
        'User-Agent' => 'Magento 2 rest client (created by Zero1 https://www.zero1.co.uk)',
    ];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * Client constructor.
     * @param $baseUrl string
     * @param $username string
     * @param $password string
     * @param $options []
     */
    public function __construct(
        $baseUrl,
        $username,
        $password,
        $options = []
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
    }

    /**
     * @return mixed
     * @throws Authentication
     */
    protected function getToken()
    {
        if (!$this->token) {
            $client = new \GuzzleHttp\Client(array_merge(
                $this->options,
                [
                    'headers' => $this->commonHeaders
                ]
            ));
            /** @var \GuzzleHttp\Psr7\Response $response */
            $response = $client->request(
                'POST',
                $this->baseUrl . '/rest/V1/integration/admin/token',
                [
                    'json' => [
                        'username' => $this->username,
                        'password' => $this->password,
                    ]
                ]
            );

            $token = \GuzzleHttp\json_decode($response->getBody(), true);

            switch ($response->getStatusCode()) {
                case 200:
                    $this->token = $token;
                    break;
                default:
                    throw new Authentication(
                        $response->getStatusCode() . ' - ' . $token,
                        $response->getStatusCode()
                    );
            }
        }
        return $this->token;
    }

    /**
     * @param $key
     * @param $data
     * @return string
     */
    protected function buildQueryArray($key, $data)
    {
        $output = [];
        foreach ($data as $k => $v) {
            $output[] = $key . '[' . $k . ']=' . $v;
        }
        return implode('&', $output);
    }

    /**
     * @return \GuzzleHttp\Client
     * @throws Authentication
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = new \GuzzleHttp\Client(array_merge(
                $this->options,
                [
                    'headers' => array_merge(
                        $this->commonHeaders,
                        [
                            'Authorization' => 'Bearer ' . $this->getToken()
                        ]
                    )
                ]
            ));
        }

        return $this->client;
    }

    /**
     * @param array $where
     * @param null|array $orderBy
     * @param int $page
     * @param int $limit
     * @return SearchCriteria
     * @throws InvalidArgument
     */
    protected function buildQuery($where = [], $orderBy = null, $page = 1, $limit = 100)
    {
        $searchCriteria = new SearchCriteria();
        foreach ($where as $filterGroup) {
            $searchCriteria->filterGroup($filterGroup);
        }
        $searchCriteria->limit($page, $limit);
        if ($orderBy) {
            $message = '$orderBy must be an array with two element \'field\' and \'direction\'.';
            if (!is_array($orderBy) || !isset($orderBy['field'], $orderBy['direction'])) {
                throw new InvalidArgument($message);
            }
        }
        return $searchCriteria;
    }

    /**
     * @param $response
     * @return mixed
     * @throws RequestFailed
     */
    private function handleResponse($response)
    {
        $body = \GuzzleHttp\json_decode($response->getBody(), true);

        switch ($response->getStatusCode()) {
            case 200:
                return $body;
            default:
                throw new RequestFailed(
                    $response->getStatusCode() . ' - ' . print_r($body, true),
                    $response->getStatusCode()
                );
        }
    }

    /**
     * @param string $sku
     * @return array
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleExceptioncatalogProductRepositoryV1
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
     * @param array $where
     * @param null|array $orderBy
     * @param int $page
     * @param int $limit
     * @return array
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @see https://devdocs.magento.com/swagger/index.html#/catalogProductRepositoryV1/catalogProductRepositoryV1GetListGet
     */
    public function getProducts($where = [], $orderBy = null, $page = 1, $limit = 100)
    {
        $searchCriteria = $this->buildQuery($where, $orderBy, $page, $limit);
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest/V1/products?' . $searchCriteria->toString()
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

    /**
     * @param null $orderBy
     * @param int $page
     * @param int $limit
     * @return mixed
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @see https://devdocs.magento.com/swagger/index.html#/catalogCategoryManagementV1/catalogCategoryManagementV1GetTreeGet
     */
    public function getAllCategories($orderBy = null, $page = 1, $limit = 100)
    {
        $searchCriteria = $this->buildQuery([], $orderBy, $page, $limit);

        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest/V1/categories?' . $searchCriteria->toString()
        );

        $body = \GuzzleHttp\json_decode($response->getBody(), true);

        switch ($response->getStatusCode()) {
            case 200:
                return $body;
            default:
                throw new RequestFailed(
                    $response->getStatusCode() . ' - ' . print_r($body, true),
                    $response->getStatusCode()
                );
        }
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
            $this->baseUrl.'/rest/V1/stockStatuses/'.$sku
        );

        return $this->handleResponse($response);
    }

    /**
     * @param array $where
     * @param null $orderBy
     * @param int $page
     * @param int $limit
     * @return mixed
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @see https://devdocs.magento.com/swagger/index.html#/catalogCategoryListV1/catalogCategoryListV1GetListGet
     */
    public function getCategories($where = [], $orderBy = null, $page = 1, $limit = 100)
    {
        $searchCriteria = $this->buildQuery($where, $orderBy, $page, $limit);
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest//V1/categories/list?' . $searchCriteria->toString()
        );

        return $this->handleResponse($response);
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
            $this->baseUrl . '/rest/V1/customers/'.$customer_id
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

    /**
     * @param array $where
     * @param null|array $orderBy
     * @param int $page
     * @param int $limit
     * @return mixed
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @see https://devdocs.magento.com/swagger/index.html#/cmsPageRepositoryV1/cmsPageRepositoryV1GetListGet
     */
    public function getCmsPages($where = [], $orderBy = null, $page = 1, $limit = 100)
    {
        $searchCriteria = $this->buildQuery($where, $orderBy, $page, $limit);
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest/V1/cmsPage/search?' . $searchCriteria->toString()
        );

        return $this->handleResponse($response);
    }

    /**
     * @param $order_id
     * @return mixed
     * @throws Authentication
     * @throws RequestFailed
     */
    public function getOrder($order_id)
    {
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest/V1/orders/' . $order_id
        );

        return $this->handleResponse($response);
    }

    /**
     * @param array $where
     * @param null $orderBy
     * @param int $page
     * @param int $limit
     * @return mixed
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     */
    public function getOrders($where = [], $orderBy = null, $page = 1, $limit = 100)
    {
        $searchCriteria = $this->buildQuery($where, $orderBy, $page, $limit);
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest/V1/orders?' . $searchCriteria->toString()
        );

        return $this->handleResponse($response);
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
     * @param $orderId
     * @param array $items
     * @param bool $notify
     * @param bool $appendComment
     * @param array $comment
     * @param array $tracks
     * @param array $packages
     * @param array $arguments
     * @return mixed
     * @throws Authentication
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function shipOrder(
        $orderId,
        $items = [],
        $notify = true,
        $appendComment = true,
        $comment = [],
        $tracks = [],
        $packages = [],
        $arguments = []
    ){
        $request = [];
        if(!empty($items)){
            $request['items'] = $items;
        }
        if(!empty($comment)){
            $request['comment'] = $comment;
        }
        if(!empty($tracks)){
            $request['tracks'] = $tracks;
        }
        if(!empty($packages)){
            $request['packages'] = $packages;
        }
        if(!empty($arguments)){
            $request['arguments'] = $arguments;
        }

        $response = $this->getClient()->request(
            'POST',
            $this->baseUrl.'/rest/V1/order/'.$orderId.'/ship',
            [
                'json' => $request
            ]
        );

        return $this->handleResponse($response);
    }

    /**
     * @param array $where
     * @param null|array $orderBy
     * @param int $page
     * @param int $limit
     * @return mixed
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @see https://devdocs.magento.com/swagger/index.html#/cmsPageRepositoryV1/cmsPageRepositoryV1GetListGet
     */
    public function getOrderItems($where = [], $orderBy = null, $page = 1, $limit = 100)
    {
        $searchCriteria = $this->buildQuery($where, $orderBy, $page, $limit);
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl.'/rest/V1/orders/items?'.$searchCriteria->toString()
        );

        return $this->handleResponse($response);
    }

    public function getProductAttribute($attribute) {

        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl.'/rest/V1/products/attributes/'.$attribute
        );

        return $this->handleResponse($response);
    }

    public function addAttribute($attribute, $label) {
        $response = $this->getClient()->request(
            'POST',
            $this->baseUrl.'/rest/V1/products/attributes',
            [
                'json' => [
                    'attribute' => [
                        'attribute_code' => $attribute,
                        'options' => [
                            'label' => $label
                        ]
                    ]
                ]
            ]
        );

        return $this->handleResponse($response);
    }
}