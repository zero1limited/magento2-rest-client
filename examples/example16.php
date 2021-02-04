<?php
include '../vendor/autoload.php';

# Get payment methods for cart

$baseUrl = $argv[1];
$username = $argv[2];
$password = $argv[3];
$cartId = $argv[4];

$body = json_decode('{
    "addressInformation": {
        "shippingAddress": {
            "countryId": "FR"
        },
        "shippingCarrierCode": "free_shipping",
        "shippingMethodCode": "default"
    }
}', true);

echo 'Connecting to store: '.$baseUrl.', with credentials: '.$username.' '.$password.PHP_EOL;

$magento = new Magento2\Client(
    $baseUrl,
    $username,
    $password,
    [
        'verify' => false,
    ]
);

$response = $magento->setShippingInformation($cartId, $body, 'db_fr');
foreach($response['totals']['total_segments'] as $segment){
    if($segment['code'] == 'discount'){
        print_r($segment);
        break;
    }
}

