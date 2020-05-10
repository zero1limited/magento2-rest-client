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


$orderIds = [];
$currentPage = 0;
$pageSize = 100;
do {
    $currentPage++;
    $orderItems = $magento->getOrderItems([
        [['sku', 'TT64300102', 'eq']]
    ], [
        'field' => 'item_id',
        'direction' => 'ASC'
    ], $currentPage, $pageSize);
    foreach($orderItems['items'] as $orderItem){
        $orderIds[$orderItem['order_id']] = 1;
    }
}while(($currentPage * $pageSize) <= $orderItems['total_count']);
$orderIds = array_keys($orderIds);
echo implode(',', $orderIds).PHP_EOL;

$currentPage = 0;
$pageSize = 100;
do {
    $currentPage++;
    $orders = $magento->getOrders([
        [['entity_id', implode(',', $orderIds), 'in']],
        [['state', implode(',', ['complete', 'canceled']), 'nin']],
        [['status', implode(',', ['complete', 'canceled']), 'nin']],
    ], [
        'field' => 'entity_id',
        'direction' => 'ASC'
    ], $currentPage, $pageSize);
    foreach($orders['items'] as $order){
        unset($order['items']);
        unset($order['extension_attributes']);
        print_r($order);
        die;
    }
}while(($currentPage * $pageSize) <= $orderItems['total_count']);


