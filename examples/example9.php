<?php
include '../vendor/autoload.php';

# Connect to an endpoint with invalid SSL

$baseUrl = $argv[1];
$username = $argv[2];
$password = $argv[3];

echo 'Connecting to store: '.$baseUrl.', with credentials: '.$username.' '.$password.PHP_EOL;

$magento = new Magento2\Client(
    $baseUrl,
    $username,
    $password,
    [
        'verify' => false,
    ]
);

$storeGroups = $magento->getStoreGroups();
print_r($storeGroups);

