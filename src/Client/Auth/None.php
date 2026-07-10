<?php
namespace Magento2API\Client\Auth;

use Magento2API\Client\AbstractAuth;

class None implements \Magento2API\Client\AuthInterface
{
    public function __invoke(callable $next)
    {
        return function (\Psr\Http\Message\RequestInterface $request, array $options) use ($next) {
            $request = $this->doSomething($request, $options);
            return $next($request, $options);
        };
    }
    
    protected function doSomething(\Psr\Http\Message\RequestInterface $request, array $options)
    {
        // No authentication needed, just return the request as is
        echo __METHOD__.' '.json_encode(func_get_args()).PHP_EOL;
        return $request;
    }
}