<?php
namespace Magento2API\Client;

interface AuthInterface
{
    public function __invoke(callable $next);
    // public function __invoke(\Psr\Http\Message\RequestInterface $request, array $options): \GuzzleHttp\Promise\PromiseInterface;
}