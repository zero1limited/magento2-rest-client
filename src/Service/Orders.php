<?php

namespace Magento2API\Service;

use Magento2API\ClientInterface;

class Orders extends AbstractService
{
    /** @var ClientInterface */
    protected $client;

    public function __construct(
        ClientInterface $client
    ){
        $this->client = $client;   
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
    
}