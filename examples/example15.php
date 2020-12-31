<?php
include '../vendor/autoload.php';

# Get product attribute options

$baseUrl = $argv[1];
$username = $argv[2];
$password = $argv[3];

echo 'Connecting to store: '.$baseUrl.', with credentials: '.$username.' '.$password.PHP_EOL;

// create file name unique to the credentials
// this way if they ever change they will be automatically
// invalidated
$credentialHash = hash('md5', implode('|', [
    $baseUrl,
    $username,
    $password
]));
$storageLocation = '/tmp/.magento2-client.'.$credentialHash.'.json';


$magento = new Magento2\Client(
    $baseUrl,
    $username,
    $password,
    [],
    new \Magento2\TokenManager($storageLocation)
);

$magento->getProductBySku('product_sku');

die;
