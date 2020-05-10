<?php
include '../vendor/autoload.php';

$baseUrl = $argv[1];
$username = $argv[2];
$password = $argv[3];

echo 'Connecting to store: '.$baseUrl.', with credentials: '.$username.' '.$password.PHP_EOL;

$magento = new Magento2\Client(
    $baseUrl,
    $username,
    $password
);

$stockItem = $magento->getStockItem('TT64300102');
print_r($stockItem);

$stockStatuses = $magento->getStockStatuses('TT64300102');
print_r($stockStatuses);
