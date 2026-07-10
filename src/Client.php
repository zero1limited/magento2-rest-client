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
use Psr\Http\Message\RequestInterface;

/**
 * Class Client
 * @package Magento2
 */
class Client implements ClientInterface
{
    protected ServiceFactory $serviceFactory;

    protected LoggerInterface $logger;

    /** @var bool */
    protected bool $autoRetry;

    /** @var int */
    protected int $maxAttempts;

    /** @var int */
    protected int $waitPeriod;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    protected ?RequestInterface $lastRequest;

    /**
     * @var array
     */
    protected $commonHeaders = [
        'Content-Type' => 'application/json',
        'User-Agent' => 'Magento 2 rest client (created by Zero1 https://www.zero1.co.uk)',
    ];

    public function __construct(
        protected string $baseUrl,
        protected AuthInterface $auth,
        protected string $storeCode = '',
        protected array $options = []
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');

        $stack = \GuzzleHttp\HandlerStack::create();
        $stack->push($auth);
        $stack->push(\GuzzleHttp\Middleware::tap(null, function (RequestInterface $request, array $options) {
            $this->lastRequest = $request;
        }));
        $this->client = new \GuzzleHttp\Client(['handler' => $stack]);

        if(isset($options['headers'], $options['headers']['User-Agent'])){
            $this->commonHeaders['User-Agent'] = $options['headers']['User-Agent'];
        }
        $this->serviceFactory = new ServiceFactory($this);
        $this->setLogger(new NullLogger());
        $this->configureAutoRetry(true, 5, 5);
    }

    public function __get($name)
    {
        return $this->serviceFactory->get($name);
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        if($this->auth && is_callable([$this->auth, 'setLogger'])){
            $this->auth->setLogger($logger);
        }
        return $this;
    }

    /**
     * @param $enable - if the auto retry logic should be enabled
     * @param $maxAttempts - the maximum number of retries for a single request
     * @param $waitPeriod - the number of seconds to wait between retries
     * @return $this
     */
    public function configureAutoRetry($enable, $maxAttempts, $waitPeriod)
    {
        $this->autoRetry = $enable;
        $this->maxAttempts = max(1, (int)$maxAttempts);
        $this->waitPeriod = max(0, (int)$waitPeriod);
        return $this;
    }

    protected function shouldRetryRequest(int $attemptCount, int $statusCode)
    {
        if($statusCode > 0 && $statusCode < 500){
            return false;
        }
        if(!$this->autoRetry || $attemptCount > $this->maxAttempts){
            return false;
        }
        if($this->waitPeriod > 0){
            sleep($this->waitPeriod);
        }
        return true;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array<mixed> $data
     * @param int|null $attemptCount
     * 
     * @return array<mixed>
     */
    public function request($method, $url, $data = [], $attemptCount = null)
    {
        if($this->storeCode){
            $url = sprintf('%s/rest/%s%s', $this->baseUrl, $this->storeCode, $url);
        }else{
            $url = sprintf('%s/rest%s', $this->baseUrl, $url);
        }

        if($attemptCount === null){
            $attemptCount = 1;
        }else{
            $attemptCount++;
        }

        $this->logger->debug('requesting', [
            'method' => $method,
            'url' => $url,
            'data' => $data,
            'attempt_count' => $attemptCount
        ]);

        try{
            /** @var \GuzzleHttp\Psr7\Response $response */
            $response = $this->client->request($method, $url, $data);
            /** @var \GuzzleHttp\Exception\ClientException $e */
        }catch(\RuntimeException $e){
            $statusCode = -1;
            $response = null;
            if($e instanceof \GuzzleHttp\Exception\RequestException){
                if($e->hasResponse()){
                    $statusCode = $e->getResponse()->getStatusCode();
                    $response = [
                        'status_code' => $statusCode,
                        'headers' => $e->getResponse()->getHeaders(),
                        'body' => (string)$e->getResponse()->getBody(),
                    ];
                }
            }

            $this->logger->error('Error connecting to endpoint', [
                'request' => [
                    'method' => $method,
                    'url' => $url,
                    'data' => $data,
                    'attempt_count' => ($attemptCount -1)
                ],
                'response' => $response,
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'trace' => $e->getTraceAsString(),
                    'class' => get_class($e),
                ]
            ]);
            if($this->shouldRetryRequest($attemptCount, $statusCode)){
                return $this->request($method, $url, $data, $attemptCount);
            }
            throw $e;
        }

        $this->logger->debug('response received', [
            'status_code' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body' => (string)$response->getBody()
        ]);
        
        $contentLength = $this->getHeaderValue($response, 'Content-Length');
        $contentType = $this->getHeaderValue($response, 'Content-Type');
        
        if(strpos($contentType, 'application/json') !== false){
            try{
                $body = \GuzzleHttp\Utils::jsonDecode($response->getBody(), true);
            }catch(\InvalidArgumentException $e){
                $this->logger->error('Invalid json response', [
                    'request' => [
                        'method' => $method,
                        'url' => $url,
                        'data' => $data,
                        'attempt_count' => ($attemptCount -1)
                    ],
                    'response' => [
                        'status_code' => $response->getStatusCode(),
                        'body' => $response->getBody()
                    ],
                    'error' => $e->getMessage(),
                ]);
                if($this->shouldRetryRequest($attemptCount, $response->getStatusCode())){
                    return $this->request($method, $url, $data, $attemptCount);
                }else{
                    throw new \GuzzleHttp\Exception\BadResponseException(
                        'Invalid json response: '.$e->getMessage().'. Raw response: '.$response->getBody(),
                        $this->lastRequest,
                        $response,
                        $e
                    );
                }
            }
        }elseif(strpos($contentType, 'text/html') !== false){
            $this->logger->error('Unexpected response content-type: text/html', [
                'request' => [
                    'method' => $method,
                    'url' => $url,
                    'data' => $data,
                    'attempt_count' => ($attemptCount -1)
                ],
                'response' => [
                    'status_code' => $response->getStatusCode(),
                    'body' => $response->getBody()
                ],
                'error' => 'Unexpected response content-type: text/html',
            ]);
            if($this->shouldRetryRequest($attemptCount, $response->getStatusCode())){
                return $this->request($method, $url, $data, $attemptCount);
            }else{
                throw new \GuzzleHttp\Exception\BadResponseException(
                    'Unexpected response content-type: text/html. Raw response: '.$response->getBody(),
                    $this->lastRequest,
                    $response,
                    null
                );
            }
        }elseif(isset($data['sink']) || $contentLength <= 0){
            // don't put downloaded file in memory
            $body = '';
        }else{
            throw new \GuzzleHttp\Exception\BadResponseException(
                'Unexpected response content-type: '.$contentType.'. Raw response: '.$response->getBody(),
                $this->lastRequest,
                $response,
                null
            );
        }

        return [
            $response->getStatusCode(),
            $body,
            $response
        ];
    }

    protected function getHeaderValue($response, $headerName)
    {
        $headerValue = $response->getHeader($headerName);
        if(is_array($headerValue)){
            return $headerValue[0] ?? null;
        }
        return $headerValue;
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
            case 400:
                if(isset($body['message']) && strpos($body['message'], 'Database deadlock found when trying to get lock') !== false){
                    throw new DatabaseDeadlock($body['message'], $response->getStatusCode());
                }
            case 404:
                if(isset($body['message'])){
                    if($body['message'] == 'The product that was requested doesn\'t exist. Verify the product and try again.'){
                        throw new EntityNotFoundException($body['message'], $response->getStatusCode());
                    }
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
     * @parem [] $fieldFilters
     * @return SearchCriteria
     * @throws InvalidArgument
     */
    public function buildQuery($where = [], $orderBy = null, $page = 1, $limit = 100, $fieldfilters = [])
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

        if(!empty($fieldfilters)){
            $searchCriteria->custom('fields', implode(',', $fieldfilters));
        }

        return $searchCriteria;
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
}
