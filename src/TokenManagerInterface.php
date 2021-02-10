<?php
namespace Magento2;

interface TokenManagerInterface
{
    /**
     * Check if a token exists
     * @return bool
     */
    public function hasToken();

    /**
     * If the current token is expired.
     * (This will also return true if there is no token) 
     * @return bool
     */
    public function isTokenExpired();

    /**
     * A token for accessing Magento 2 API
     * @param $token string
     * @return $this
     */
    public function setToken($token);

    /**
     * The current token if one has been set
     * @return string|null
     */
    public function getToken();

    /**
     * Get the unix timestamp for the expected expiry time
     * @return int
     */
    public function getExpiryTime();

    /**
     * import credentials from storage
     * @return void
     */
    public function importFromStorage();
}