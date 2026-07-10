<?php
namespace Magento2API\Client\Auth;

use Magento2API\Client\AuthInterface;

class UsernameAndPassword implements AuthInterface
{

    /**
     * @return mixed
     * @throws Authentication
     */
    protected function getToken()
    {
        // if we have a valid token return it
        if($this->token instanceof TokenManagerInterface){
            if(!$this->token->isTokenExpired()){
                return $this->token->getToken();
            }
        }else{
            if ($this->token !== null) {
                return $this->token;
            }
        }

        // ...otherwise go and fetch a new token.
        $client = new \GuzzleHttp\Client(
            array_merge(
                $this->options,
                [
                    'headers' => $this->commonHeaders,
                    'http_errors' => false,
                ]
            )
        );
        /** @var \GuzzleHttp\Psr7\Response $response */
        $response = $client->request(
            'POST',
            $this->baseUrl . '/rest/V1/integration/admin/token',
            [
                'json' => [
                    'username' => $this->username,
                    'password' => $this->password,
                ]
            ]
        );

        try{
            $token = \GuzzleHttp\json_decode(trim($response->getBody()), true);
        }catch(\Exception $e){
            throw new RequestFailed(
                'Invalid response: '.$e->getMessage().'. Raw response: '.$response->getBody(),
                $response->getStatusCode()
            );
        }

        switch ($response->getStatusCode()) {
            case 200:
                if($this->token instanceof TokenManagerInterface){
                    $this->token->setToken($token);
                }else{
                    $this->token = trim($token);
                }
                break;
            default:
                throw new Authentication(
                    $response->getStatusCode() . ' - ' . $response->getBody(),
                    $response->getStatusCode()
                );
        }

        if($this->token instanceof TokenManagerInterface){
            return $this->token->getToken();
        }else{
            return $this->token;
        }
    }
}