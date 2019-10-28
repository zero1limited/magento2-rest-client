<?php
namespace Magento2;

use Dsheiko\SearchCriteria;
use Magento2\Exception\Authentication;
use Magento2\Exception\InvalidArgument;
use Magento2\Exception\RequestFailed;

class Client
{
    protected $baseUrl;

    protected $username;

    protected $password;

    protected $token;

    protected $client;

    protected $commonHeaders = [
        'Content-Type' => 'application/json',
        'User-Agent' => 'Magento 2 rest client (created by Zero1 https://www.zero1.co.uk)',
    ];

    public function __construct(
        $baseUrl,
        $username,
        $password
    ){
        $this->baseUrl = $baseUrl;
        $this->username = $username;
        $this->password = $password;
    }

    protected function getToken()
    {
        if(!$this->token){
            $client = new \GuzzleHttp\Client([
                'headers' => $this->commonHeaders
            ]);
            /** @var \GuzzleHttp\Psr7\Response $response */
            $response = $client->request(
                'POST',
                $this->baseUrl.'/rest/V1/integration/admin/token',
                [
                    'json' => [
                        'username' => $this->username,
                        'password' => $this->password,
                    ]
                ]
            );

            $token = \GuzzleHttp\json_decode($response->getBody(), true);

            switch($response->getStatusCode()){
                case 200:
                    $this->token = $token;
                    break;
                default:
                    throw new Authentication(
                        $response->getStatusCode().' - '.$token,
                        $response->getStatusCode()
                    );
            }
        }
        return $this->token;
    }

    /**
     * @return \GuzzleHttp\Client
     * @throws Authentication
     */
    public function getClient()
    {
        if(!$this->client){
            $this->client = new \GuzzleHttp\Client([
                'headers' => array_merge($this->commonHeaders, [
                    'Authorization' => 'Bearer '.$this->getToken()
                ])
            ]);
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
        foreach($where as $filterGroup){
            $searchCriteria->filterGroup($filterGroup);
        }
        $searchCriteria->limit($page, $limit);
        if($orderBy){
            $message = '$orderBy must be an array with two element \'field\' and \'direction\'.';
            if(!is_array($orderBy) || !isset($orderBy['field'], $orderBy['direction'])){
                throw new InvalidArgument($message);
            }
        }
        return $searchCriteria;
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
     */
    public function getProducts($where = [], $orderBy = null, $page = 1, $limit = 100)
    {
        $searchCriteria = $this->buildQuery($where, $orderBy, $page, $limit);
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl.'/rest/V1/products?'.$searchCriteria->toString()
        );

        $body = \GuzzleHttp\json_decode($response->getBody(), true);

        switch($response->getStatusCode()){
            case 200:
                return $body;
            default:
                throw new RequestFailed(
                    $response->getStatusCode().' - '.print_r($body, true),
                    $response->getStatusCode()
                );
        }
    }

    public function getStockItem($sku)
    {
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl.'/rest/V1/stockItems/'.$sku
        );

        $body = \GuzzleHttp\json_decode($response->getBody(), true);

        switch($response->getStatusCode()){
            case 200:
                return $body;
            default:
                throw new RequestFailed(
                    $response->getStatusCode().' - '.print_r($body, true),
                    $response->getStatusCode()
                );
        }
    }
}