<?php
include '../vendor/autoload.php';

# Get product attribute options

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

$options = $magento->getProductAttributeOptions('manufacturer');
print_r($options);
die;
