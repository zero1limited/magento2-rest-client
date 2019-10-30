<?php
include '../vendor/autoload.php';

$baseUrl = $argv[1];
$username = $argv[2];
$password = $argv[3];

echo 'Connecting to store: '.$baseUrl.', with credentials: '.$username.' '.$password.PHP_EOL;
echo 'Finding upto 10 in enabled categories'.PHP_EOL;

$magento = new Magento2\Client(
    $baseUrl,
    $username,
    $password
);

$categories = $magento->getCategories([
    [['level', 1, 'gt']],
    [['include_in_menu', 1, 'eq']],
    [['path', '%/3/%', 'like']],
    [['is_active', 1, 'eq']]
]);
$enabledCategories = [];
foreach($categories['items'] as $category){
    $enabledCategories[$category['id']] = $category;
    if(count($enabledCategories) == 10){
        break;
    }
}

echo 'found categories: '.print_r(array_keys($enabledCategories), true).PHP_EOL;