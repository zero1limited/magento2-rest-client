<?php

namespace Magento2;

use Dsheiko\SearchCriteria;
use Exception;
use Magento2\Client\Exception\Authentication;
use Magento2\Client\Exception\DatabaseDeadlock;
use Magento2\Client\Exception\EntityNotFoundException;
use Magento2\Client\Exception\InvalidArgument;
use Magento2\Client\Exception\RequestFailed;

/**
 * Class Client
 * @package Magento2
 */
class Client
{
    /**
     * Maximum number of times to repeat valid requests
     * (e.g if we receive DB lock wait timeout)
     */
    const MAX_ATTEMPTS = 10;

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
     * @param $token string|null|TokenManagerInterfaceM
     */
    public function __construct(
        $baseUrl,
        $username,
        $password,
        $options = [],
        $token = null
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;

        if ($token !== null) {
            $this->token = $token;
        }

        if(isset($options['headers'], $options['headers']['User-Agent'])){
            $this->commonHeaders['User-Agent'] = $options['headers']['User-Agent'];
        }
    }

    /**
     * @return \GuzzleHttp\Client
     * @throws Authentication
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = new \GuzzleHttp\Client(
                array_merge(
                    $this->options,
                    [
                        'headers' => array_merge(
                            $this->commonHeaders,
                            [
                                'Authorization' => 'Bearer ' . $this->getToken()
                            ]
                        ),
                        'http_errors' => false
                    ]
                )
            );
        }

        return $this->client;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return mixed
     * @throws Authentication
     */
    protected function getToken()
    {
        // if we have a valid token return it
        if($this->token instanceof TokenManagerInterface){
            if(!$this->token->isTokenExpired()){
                return $this->token->getToken();
            }
        }else{
            if ($this->token !== null) {
                return $this->token;
            }
        }

        // ...otherwise go and fetch a new token.
        $client = new \GuzzleHttp\Client(
            array_merge(
                $this->options,
                [
                    'headers' => $this->commonHeaders,
                    'http_errors' => false,
                ]
            )
        );
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

        try{
            $token = \GuzzleHttp\json_decode(trim($response->getBody()), true);
        }catch(\Exception $e){
            throw new RequestFailed(
                'Invalid response: '.$e->getMessage().'. Raw response: '.$response->getBody(),
                $response->getStatusCode()
            );
        }

        switch ($response->getStatusCode()) {
            case 200:
                if($this->token instanceof TokenManagerInterface){
                    $this->token->setToken($token);
                }else{
                    $this->token = trim($token);
                }
                break;
            default:
                throw new Authentication(
                    $response->getStatusCode() . ' - ' . $response->getBody(),
                    $response->getStatusCode()
                );
        }

        if($this->token instanceof TokenManagerInterface){
            return $this->token->getToken();
        }else{
            return $this->token;
        }
    }

    /**
     * @param $method string
     * @param $url string
     * @param $data []
     * @return mixed
     * @throws Authentication
     * @throws DatabaseDeadlock
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function request($method, $url, $data)
    {
        $counter = 0;
        do{
            $exception = null;
            sleep($counter);
            $counter++;
            try{
                $response = $this->handleResponse(
                    $this->getClient()->request(
                        $method, $url, $data
                    )
                );
            }catch (DatabaseDeadlock $e){
                $exception = $e;
            }
        }while($exception != null && $counter < self::MAX_ATTEMPTS);

        if($exception){
            throw $exception;
        }
        return $response;
    }

    /**
     * @param $response \GuzzleHttp\Psr7\Response
     * @return mixed
     * @throws RequestFailed
     */
    private function handleResponse($response)
    {
        $body = null;
        try{
            $body = \GuzzleHttp\json_decode($response->getBody(), true);
        }catch(\Exception $e){
            throw new RequestFailed(
                'Invalid response: '.$e->getMessage().'. Raw response: '.$response->getBody(),
                $response->getStatusCode()
            );
        }

        switch ($response->getStatusCode()) {
            case 200:
                return $body;
            case 404:
                if(isset($body['message'])){
                    if($body['message'] == 'The product that was requested doesn\'t exist. Verify the product and try again.'){
                        throw new EntityNotFoundException($body['message'], $response->getStatusCode());
                    }
                }
            case 400:
                if(isset($body['message']) && strpos($body['message'], 'Database deadlock found when trying to get lock') !== false){
                    throw new DatabaseDeadlock($body['message'], $response->getStatusCode());
                }
            default:
                throw new RequestFailed(
                    $response->getStatusCode() . ' - ' . print_r($body, true),
                    $response->getStatusCode()
                );
        }
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
     * @param array $where
     * @param null|array $orderBy
     * @param int $page
     * @param int $limit
     * @return SearchCriteria
     * @throws InvalidArgument
     */
    public function buildQuery($where = [], $orderBy = null, $page = 1, $limit = 100)
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
    public function getAllCategories($orderBy = null, $page = 1, $limit = 250)
    {
        $outputCategories = [];

        do{
            $categories = $this->getCategories([], $orderBy, $page, $limit);
            array_push($outputCategories, ...$categories['items']);
            $totalItems = $categories['total_count'];
            $page++;
        }while((($page - 1) * $limit) <= $totalItems);

        return $outputCategories;
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
            $this->baseUrl . '/rest/V1/categories/list?' . $searchCriteria->toString()
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
            $this->baseUrl . '/rest/V1/customers/' . $customer_id
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
    ) {
        $request = [];
        if (!empty($items)) {
            $request['items'] = $items;
        }
        if (!empty($comment)) {
            $request['comment'] = $comment;
        }
        if (!empty($tracks)) {
            $request['tracks'] = $tracks;
        }
        if (!empty($packages)) {
            $request['packages'] = $packages;
        }
        if (!empty($arguments)) {
            $request['arguments'] = $arguments;
        }

        $response = $this->getClient()->request(
            'POST',
            $this->baseUrl . '/rest/V1/order/' . $orderId . '/ship',
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
            $this->baseUrl . '/rest/V1/orders/items?' . $searchCriteria->toString()
        );

        return $this->handleResponse($response);
    }

    /**
     * Get all of the options for a product attribute.
     *
     * @param string $attribute
     * @return mixed
     * @throws Authentication
     * @throws RequestFailed
     */
    public function getProductAttributeOptions($attribute, $storeCode = 'default')
    {
        $attributes = [];

        try {
            $response = $this->getClient()->request(
                'GET',
                $this->baseUrl . '/rest/'.$storeCode.'/V1/products/attributes/' . $attribute
            );

            $response = $this->handleResponse($response);

            $attributes = $response['options'];
        } catch (Exception $e) {
        }

        return $attributes;
    }

    /**
     * Get data about a product attribute.
     *
     * @param string $attribute
     * @return mixed
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getProductAttribute($attribute)
    {
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest/V1/products/attributes/' . $attribute
        );

        return $this->handleResponse($response);
    }

    /**
     * Add a label to a product attribute.
     * @param string $attribute
     * @param string $label
     * @return mixed
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addProductAttributeLabel($attribute, $label, $storeLabels = null)
    {
        $postData = [
            'label' => $label
        ];
        if($storeLabels){
            $postData['store_labels'] = $storeLabels;
        }
        $response = $this->getClient()->request(
            'POST',
            $this->baseUrl . '/rest/V1/products/attributes/' . $attribute . '/options',
            [
                'json' => [
                    'option' => $postData
                ]
            ]
        );
        return $this->handleResponse($response);
    }

    /**
     * Delete an product attribute label.
     *
     * @param string $attribute
     * @param int $label_id
     * @return mixed
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deleteProductAttributeLabel($attribute, int $label_id)
    {
        $response = $this->getClient()->request(
            'DELETE',
            $this->baseUrl . '/rest/V1/products/attributes/' . $attribute . '/options/' . $label_id
        );

        return $this->handleResponse($response);
    }

    /**
     * @param string $sku
     * @return array
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleExceptioncatalogProductRepositoryV1
     * @throws \Magento2\Client\Exception\EntityNotFoundException
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
     * @param array $product
     * @return mixed
     *
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Magento2\Client\Exception\EntityNotFoundException
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
     * Add a comment to an order.
     *
     * @param int $orderId The Magento Order ID to attach comment to.
     * @param $status Order status code - this needs to be passed even if it isn't being changed.
     * @param $comment The actual comment text to store.
     * @param int $is_customer_notified Whether to notify the customer of this comment.
     *
     * @return mixed
     * @throws Authentication
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addOrderComment(int $orderId, $status, $comment, $is_customer_notified = 0)
    {
        $response = $this->getClient()->request(
            'POST',
            $this->baseUrl . '/rest/V1/orders/' . $orderId . '/comments',
            [
                'json' => [
                    "statusHistory" => [
                        "comment" => $comment,
                        "status" => $status,
                        "parent_id" => $orderId,
                        "is_customer_notified" => $is_customer_notified,
                        "is_visible_on_front" => 1
                    ]
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
     * @param $inc_order_id
     * @return mixed
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     */
    public function getOrderByIncrementId($inc_order_id)
    {

        $order = $this->getOrders([
            [['increment_id', $inc_order_id, 'eq']]
        ]);

        $order = $order['items'][0];

        return $order;
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
     * Add a product to a category
     * @param string $attribute
     * @param string $label
     * @return mixed
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addProductToCategory($sku, $categoryId, $position = null, $extensionAttributes = null)
    {
        $payload = [
            'productLink' => [
                'sku' => $sku,
                'category_id' => $categoryId,
            ]
        ];
        if($position !== null){
            $payload['productLink']['position'] = $position;
        }
        if($extensionAttributes !== null){
            $payload['productLink']['extension_attributes'] = $extensionAttributes;
        }
        $response = $this->getClient()->request(
            'POST',
            $this->baseUrl . '/rest/V1/categories/'.$categoryId.'/products',
            [
                'json' => $payload
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
    public function getMediaGalleryEntries($sku)
    {
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest/V1/products/'.$sku.'/media'
        );
        return $this->handleResponse($response);
    }

    /**
     * @param string $sku
     * @param array $entryData
     * @param string $storeCode
     * @return mixed
     *
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @see https://magento.redoc.ly/2.3.6-admin/tag/productsskumedia#operation/catalogProductAttributeMediaGalleryManagementV1CreatePost
     */
    public function addMediaGalleryEntry($sku, $entryData, $storeCode = 'default')
    {
        $response = $this->getClient()->request(
            'POST',
            $this->baseUrl.'/rest/'.$storeCode.'/V1/products/'.$sku.'/media',
            [
                'json' => [
                    'entry' => $entryData
                ]
            ]
        );
        return $this->handleResponse($response);
    }

    /**
     * @param string $sku
     * @param string|int $entryId
     * @param array $entryData
     * @param string $storeCode
     * @return mixed
     *
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @see https://magento.redoc.ly/2.3.6-admin/tag/productsskumedia#operation/catalogProductAttributeMediaGalleryManagementV1UpdatePut
     */
    public function updateMediaGalleryEntry($sku, $entryId, $entryData, $storeCode = 'default')
    {
        $response = $this->getClient()->request(
            'PUT',
            $this->baseUrl.'/rest/'.$storeCode.'/V1/products/'.$sku.'/media/'.$entryId,
            [
                'json' => [
                    'entry' => $entryData
                ]
            ]
        );
        return $this->handleResponse($response);
    }

    /**
     * @param string $sku
     * @param string|int $entryId
     * @return mixed
     *
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @see https://magento.redoc.ly/2.3.6-admin/tag/productsskumedia#operation/catalogProductAttributeMediaGalleryManagementV1UpdatePut
     */
    public function deleteMediaGalleryEntry($sku, $entryId)
    {
        $response = $this->getClient()->request(
            'DELETE',
            $this->baseUrl . '/rest/V1/products/'.$sku.'/media/'.$entryId
        );
        return $this->handleResponse($response);
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
}
