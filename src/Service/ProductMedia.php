<?php

namespace Magento2API\Service;

use Magento2API\ClientInterface;

class ProductMedia extends AbstractService
{
    /** @var ClientInterface */
    protected $client;

    public function __construct(
        ClientInterface $client
    ){
        $this->client = $client;   
    }

    

    /**
     * @param string $sku
     * @return mixed
     *
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @see https://devdocs.magento.com/swagger/index.html
     */
    public function getMediaGalleryEntries($sku)
    {
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest/V1/products/'.$sku.'/media'
        );
        return $this->handleResponse($response);
    }

    /**
     * @param string $sku
     * @param array $entryData
     * @param string $storeCode
     * @return mixed
     *
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @see https://magento.redoc.ly/2.3.6-admin/tag/productsskumedia#operation/catalogProductAttributeMediaGalleryManagementV1CreatePost
     */
    public function addMediaGalleryEntry($sku, $entryData, $storeCode = 'default')
    {
        $response = $this->getClient()->request(
            'POST',
            $this->baseUrl.'/rest/'.$storeCode.'/V1/products/'.$sku.'/media',
            [
                'json' => [
                    'entry' => $entryData
                ]
            ]
        );
        return $this->handleResponse($response);
    }

    /**
     * @param string $sku
     * @param string|int $entryId
     * @param array $entryData
     * @param string $storeCode
     * @return mixed
     *
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @see https://magento.redoc.ly/2.3.6-admin/tag/productsskumedia#operation/catalogProductAttributeMediaGalleryManagementV1UpdatePut
     */
    public function updateMediaGalleryEntry($sku, $entryId, $entryData, $storeCode = 'default')
    {
        $response = $this->getClient()->request(
            'PUT',
            $this->baseUrl.'/rest/'.$storeCode.'/V1/products/'.$sku.'/media/'.$entryId,
            [
                'json' => [
                    'entry' => $entryData
                ]
            ]
        );
        return $this->handleResponse($response);
    }

    /**
     * @param string $sku
     * @param string|int $entryId
     * @return mixed
     *
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @see https://magento.redoc.ly/2.3.6-admin/tag/productsskumedia#operation/catalogProductAttributeMediaGalleryManagementV1UpdatePut
     */
    public function deleteMediaGalleryEntry($sku, $entryId)
    {
        $response = $this->getClient()->request(
            'DELETE',
            $this->baseUrl . '/rest/V1/products/'.$sku.'/media/'.$entryId
        );
        return $this->handleResponse($response);
    }

    
}