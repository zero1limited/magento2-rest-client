<?php

namespace Magento2API\Service;

use Magento2API\ClientInterface;

class CmsPages extends AbstractService
{
    /** @var ClientInterface */
    protected $client;

    public function __construct(
        ClientInterface $client
    ){
        $this->client = $client;   
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
    public function getCmsPages($where = [], $orderBy = null, $page = 1, $limit = 100)
    {
        $searchCriteria = $this->buildQuery($where, $orderBy, $page, $limit);
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest/V1/cmsPage/search?' . $searchCriteria->toString()
        );

        return $this->handleResponse($response);
    }
}