<?php

namespace Magento2API\Service;

use Magento2API\ClientInterface;

class ProductAttributes extends AbstractService
{
    /** @var ClientInterface */
    protected $client;

    public function __construct(
        ClientInterface $client
    ){
        $this->client = $client;   
    }



    /**
     * Get all of the options for a product attribute.
     *
     * @param string $attribute
     * @return mixed
     * @throws Authentication
     * @throws RequestFailed
     */
    public function getProductAttributeOptions($attribute, $storeCode = 'default')
    {
        $attributes = [];

        try {
            $response = $this->getClient()->request(
                'GET',
                $this->baseUrl . '/rest/'.$storeCode.'/V1/products/attributes/' . $attribute
            );

            $response = $this->handleResponse($response);

            $attributes = $response['options'];
        } catch (Exception $e) {
        }

        return $attributes;
    }

    /**
     * Get data about a product attribute.
     *
     * @param string $attribute
     * @return mixed
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getProductAttribute($attribute)
    {
        $response = $this->getClient()->request(
            'GET',
            $this->baseUrl . '/rest/V1/products/attributes/' . $attribute
        );

        return $this->handleResponse($response);
    }

    /**
     * Add a label to a product attribute.
     * @param string $attribute
     * @param string $label
     * @return mixed
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addProductAttributeLabel($attribute, $label, $storeLabels = null)
    {
        $postData = [
            'label' => $label
        ];
        if($storeLabels){
            $postData['store_labels'] = $storeLabels;
        }
        $response = $this->getClient()->request(
            'POST',
            $this->baseUrl . '/rest/V1/products/attributes/' . $attribute . '/options',
            [
                'json' => [
                    'option' => $postData
                ]
            ]
        );
        return $this->handleResponse($response);
    }

    /**
     * Delete an product attribute label.
     *
     * @param string $attribute
     * @param int $label_id
     * @return mixed
     * @throws Authentication
     * @throws InvalidArgument
     * @throws RequestFailed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deleteProductAttributeLabel($attribute, int $label_id)
    {
        $response = $this->getClient()->request(
            'DELETE',
            $this->baseUrl . '/rest/V1/products/attributes/' . $attribute . '/options/' . $label_id
        );

        return $this->handleResponse($response);
    }
    
}