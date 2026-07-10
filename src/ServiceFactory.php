<?php

namespace Magento2API;

use Magento2API\ClientInterface;
use Magento2API\Service\AbstractService;

/**
 * Class ServiceFactory
 * @package Magento2API
 */
class ServiceFactory
{
    private static $classMap = [
        'products' => \Magento2API\Service\Products::class,
        'productAttributes' => \Magento2API\Service\ProductAttributes::class,
        'productMedia' => \Magento2API\Service\ProductMedia::class,
        'categories' => \Magento2API\Service\Categories::class,
        'customers' => \Magento2API\Service\Customers::class,
        'orders' => \Magento2API\Service\Orders::class,
        'cmsPages' => \Magento2API\Service\CmsPages::class,
        'stock' => \Magento2API\Service\Stock::class,
        'store' => \Magento2API\Service\Store::class,
        'guestCart' => \Magento2API\Service\GuestCart::class,
        'search' => \Magento2API\Service\Search::class,
    ];

    /** @var array<string, \Magento2API\Service\AbstractService> */
    protected $services = [];
    
    /** @var ClientInterface */
    protected $client;

    public function __construct(
        ClientInterface $client
    ){
        $this->client = $client;    
    }

    /**
     * @param string $name
     * @return AbstractService
     */
    public function get($name)
    {
        if(!isset($this->services[$name])){
            if(!isset(self::$classMap[$name])){
                throw new \Exception('Service "'.$name.'" is not recognized.');
            }
            $this->services[$name] = new self::$classMap[$name]($this->client);
        }
        return $this->services[$name];
    }
}
