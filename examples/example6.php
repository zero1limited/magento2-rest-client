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

$response = $magento->shipOrder(
    67820,
    [
        ['order_item_id' => 73673, 'qty' => 3],
        ['order_item_id' => 73674, 'qty' => 1],
        ['order_item_id' => 73675, 'qty' => 1],
    ],
    false,
    true,
    [
        'comment' => 'Zero1 test ship',
        'isVisibleOnFront' => 0,
    ],
    [
        [
            'track_number' => 'Not Tracked',
            'title' => 'Manual',
            'carrier_code' => 'custom',
        ]
    ]
);

