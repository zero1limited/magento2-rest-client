<?php

namespace Magento2API\Service;

use Magento2API\ClientInterface;

class Search extends AbstractService
{
    /** @var ClientInterface */
    protected $client;

    public function __construct(
        ClientInterface $client
    ){
        $this->client = $client;   
    }


    /**
     * @param string $term - the search term to search for
     * @param array $additionalFilters - additional filters to apply to the search
     * [
     *  [
     *      'field' => 'category_id',
     *      'value' => 21,
     *      'condition_type' => 'eq'
     *  ],
     *  [
     *      'field' => 'another_attribute',
     *      'value' => 'foo',
     *      'condition_type' => 'eq'
     *  ],
     * ]
     * @parem array $tierPrices
     * @return mixed
     *
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @see https://adobe-commerce.redoc.ly/2.4.8-guest/tag/search#operation/GetV1Search
     */
    public function term(string $term, $additionalFilters = [])
    {
        $additionalFilters[] = [
            'field' => 'search_term',
            'value' => $term,
            'condition_type' => 'eq'
        ];
        $searchCriteria = [
            'searchCriteria' => [
                'request_name' => 'quick_search_container',
                'filter_groups' => [
                    [
                        'filters' => $additionalFilters
                    ]
                ],
                'page_size' => 300,
                'current_page' => 0,
            ],
        ];
        $query = http_build_query($searchCriteria);

        list($statusCode, $body, $response) = $this->client->request(
            'GET',
            '/V1/search?'.$query
        );

        echo 'statusCode: '.$statusCode.PHP_EOL;
        echo 'body: '.print_r($body, true).PHP_EOL;
        die(__FILE__.':'.__LINE__);
    }
}