<?php

namespace Magento2API\Service;

use Magento2API\ClientInterface;

class Categories extends AbstractService
{
    /** @var ClientInterface */
    protected $client;

    public function __construct(
        ClientInterface $client
    ){
        $this->client = $client;   
    }


    /**
     * Add a product to a category
     * @param string $attribute
     * @param string $label
     * @return mixed
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addProductToCategory($sku, $categoryId, $position = null, $extensionAttributes = null)
    {
        $payload = [
            'productLink' => [
                'sku' => $sku,
                'category_id' => $categoryId,
            ]
        ];
        if($position !== null){
            $payload['productLink']['position'] = $position;
        }
        if($extensionAttributes !== null){
            $payload['productLink']['extension_attributes'] = $extensionAttributes;
        }
        $response = $this->getClient()->request(
            'POST',
            $this->baseUrl . '/rest/V1/categories/'.$categoryId.'/products',
            [
                'json' => $payload
            ]
        );
        return $this->handleResponse($response);
    }

    

    /**
     * @param array $where
     * @param null $orderBy
     * @param int $page
     * @param int $limit
     * @return mixed
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @see https://devdocs.magento.com/swagger/index.html#/catalogCategoryListV1/catalogCategoryListV1GetListGet
     */
    public function getCategories($where = [], $orderBy = null, $page = 1, $limit = 100)
    {
        $searchCriteria = $this->buildQuery($where, $orderBy, $page, $limit);
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest/V1/categories/list?' . $searchCriteria->toString()
        );

        return $this->handleResponse($response);
    }

    /**
     * @param null $orderBy
     * @param int $page
     * @param int $limit
     * @return mixed
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @see https://devdocs.magento.com/swagger/index.html#/catalogCategoryManagementV1/catalogCategoryManagementV1GetTreeGet
     */
    public function getAllCategories($orderBy = null, $page = 1, $limit = 250)
    {
        $outputCategories = [];
        do{
            $categories = $this->getCategories([], $orderBy, $page, $limit);
            array_push($outputCategories, ...$categories['items']);
            $totalItems = $categories['total_count'];
            $page++;
        }while((($page - 1) * $limit) <= $totalItems);

        return $outputCategories;
    }
}