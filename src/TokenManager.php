<?php
namespace Magento2;

class TokenManager implements TokenManagerInterface
{
    const MAX_FILE_WAIT_TIME = 10;

    protected $storageLocation;

    protected $tokenLifetime;

    protected $token;

    protected $tokenExpriation;

    public function __construct(
        $storageLocation = '.magento2-restclient.json',
        $tokenLifetime = 10800
    ){
        $this->storageLocation = $storageLocation;
        $this->tokenLifetime = $tokenLifetime;
    }

    /**
     * Check if a token exists
     * @return bool
     */
    public function hasToken()
    {
        if(!$this->token){
            $this->importFromStorage();
        }
        return (bool)$this->token;
    }

    /**
     * If the current token is expired.
     * (This will also return true if there is no token)
     * @return bool
     */
    public function isTokenExpired()
    {
        if(!$this->hasToken()){
            return true;
        }
        return time() >= $this->tokenExpriation;
    }

    /**
     * A token for accessing Magento 2 API
     * @param $token string
     * @return $this
     */
    public function setToken($token)
    {
        $handle = fopen($this->storageLocation,'a+');
        $this->lock($handle);
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode([
            'token' => $token,
            'expiration' => (time() + $this->tokenLifetime)
        ]));
        $this->unlock($handle);
        $this->importFromStorage();
    }

    protected function lock($handle)
    {
        $startTime = time();
        while(!flock($handle, LOCK_EX | LOCK_NB)){
            if ((time() - $startTime) > self::MAX_FILE_WAIT_TIME) {
                throw new \Exception('Unable to obtain lock for storage resource');
            }
            usleep(100);
        }
    }

    protected function unlock($handle)
    {
        @flock($handle, LOCK_UN);
        @fclose($handle);
    }

    public function importFromStorage()
    {
        if(is_file($this->storageLocation)){
            $handle = fopen($this->storageLocation,"r+");
            $this->lock($handle);
            $data = json_decode(fread($handle, filesize($this->storageLocation)), true);
            $this->unlock($handle);
            $this->token = $data['token'];
            $this->tokenExpriation = $data['expiration'];
        }
    }

    /**
     * The current token if one has been set
     * @return string|null
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Get the unix timestamp for the expected expiry time
     * @return int
     */
    public function getExpiryTime()
    {
        return $this->tokenExpriation;
    }
}