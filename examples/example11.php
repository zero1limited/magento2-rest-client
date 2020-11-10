<?php
include '../vendor/autoload.php';

# Custom Endpoint

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
//  call custom endpoint
$response = $magento->getClient()->request(
    'GET',
    $magento->getBaseUrl().'/rest/V1/a/custom/endpoint'
);
$body = \GuzzleHttp\json_decode($response->getBody(), true);
print_r($body);
die;
