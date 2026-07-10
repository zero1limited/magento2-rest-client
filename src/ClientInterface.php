<?php

namespace Magento2API;

use Dsheiko\SearchCriteria;
use Exception;
use Magento2API\Client\AuthInterface;
use Magento2API\Client\AbstractAuth;
use Magento2API\Client\Exception\Authentication;
use Magento2API\Client\Exception\DatabaseDeadlock;
use Magento2API\Client\Exception\EntityNotFoundException;
use Magento2API\Client\Exception\InvalidArgument;
use Magento2API\Client\Exception\RequestFailed;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Magento2API\ServiceFactory;

/** 
 * Interface ClientInterface
 * @package Magento2API
 * 
 * @property \Magento2API\Service\Categories $categories
 * @property \Magento2API\Service\CmsPages $cmsPages
 * @property \Magento2API\Service\Customers $customers
 * @property \Magento2API\Service\GuestCart $guestCart
 * @property \Magento2API\Service\Orders $orders
 * @property \Magento2API\Service\ProductAttributes $productAttributes
 * @property \Magento2API\Service\ProductMedia $productMedia
 * @property \Magento2API\Service\Products $products
 * @property \Magento2API\Service\Search $search
 * @property \Magento2API\Service\Stock $stock
 * @property \Magento2API\Service\Store $store
 */
interface ClientInterface
{
    /**
     * @param string $method
     * @param string $url
     * @param array<mixed> $data
     * @param int|null $attemptCount
     * 
     * @return array<mixed>
     */
    public function request($method, $url, $data = [], $attemptCount = null);
}
