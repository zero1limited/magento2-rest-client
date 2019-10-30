<?php
include '../vendor/autoload.php';

$baseUrl = $argv[1];
$username = $argv[2];
$password = $argv[3];

echo 'Connecting to store: '.$baseUrl.', with credentials: '.$username.' '.$password.PHP_EOL;
echo 'Finding upto 10 in stock simple products'.PHP_EOL;

$magento = new Magento2\Client(
    $baseUrl,
    $username,
    $password
);

$product = $magento->getProducts([
    [['type_id', 'simple', 'eq']],
    [['status', 1, 'eq']],
    [['visibility', 1, 'gt']]
]);
$inStockProducts = [];
foreach($product['items'] as $product){
    $sku = $product['sku'];
    $stockItem = $magento->getStockItem($sku);
    if($stockItem['is_in_stock'] == 1 && $stockItem['qty'] > 0){
        $inStockProducts[$sku] = [
            'product' => $product,
            'stock_item' => $stockItem
        ];
    }
    if(count($inStockProducts) == 10){
        break;
    }
}

echo 'found products: '.print_r(array_keys($inStockProducts), true).PHP_EOL;