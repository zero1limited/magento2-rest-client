<?php
include '../vendor/autoload.php';

# Update a product

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

$product = $magento->updateProduct('PRODUCT_SKU', [
    'price' => 9.20,
]);
print_r($product);
die;
