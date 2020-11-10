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

try{
    $product = $magento->getProductBySku('fooooo');
    print_r($product);
}catch(\Magento2\Client\Exception\EntityNotFoundException $e){
    echo 'product doesn\'t exist'.PHP_EOL;
}
die;
