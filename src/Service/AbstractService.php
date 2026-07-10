<?php

namespace Magento2API\Service;

use Magento2API\ClientInterface;

abstract class AbstractService
{
    /** @var ClientInterface */
    protected $client;

    public function __construct(
        ClientInterface $client
    ){
        $this->client = $client;   
    }
}