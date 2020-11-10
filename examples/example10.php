<?php
include '../vendor/autoload.php';

# Get payment methods for cart

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

$storeGroups = $magento->getPaymentMethods('knUGtnybfSrq25ltcxLTxi45DO0udERU');
print_r($storeGroups);

