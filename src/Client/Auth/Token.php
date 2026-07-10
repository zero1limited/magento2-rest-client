<?php
namespace Magento2API\Client\Auth;

use Magento2API\Client\AuthInterface;

class Token implements AuthInterface
{

    /**
     * @return \GuzzleHttp\Client
     * @throws Authentication
     */
    public function getClient()
    {
        if($this->token instanceof TokenManagerInterface){
            if($this->token->isTokenExpired()){
                // for client to be reconstructed
                $this->client = null;
            }
            // attempt to load new details from storage
            $this->token->importFromStorage();
        }

        if (!$this->client) {
            $this->client = new \GuzzleHttp\Client(
                array_merge(
                    $this->options,
                    [
                        'headers' => array_merge(
                            $this->commonHeaders,
                            [
                                'Authorization' => 'Bearer ' . $this->getToken()
                            ]
                        ),
                        'http_errors' => false
                    ]
                )
            );
        }

        return $this->client;
    }
}