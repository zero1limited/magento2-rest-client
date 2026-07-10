<?php
namespace Magento2API\Client;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;

abstract class AbstractAuth implements AuthInterface
{
    abstract public function __invoke(RequestInterface $request, array $options): PromiseInterface;
}