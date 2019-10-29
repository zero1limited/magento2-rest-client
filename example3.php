<?php
include 'vendor/autoload.php';

$baseUrl = $argv[1];
$username = $argv[2];
$password = $argv[3];

echo 'Connecting to store: '.$baseUrl.', with credentials: '.$username.' '.$password.PHP_EOL;
echo 'Finding upto 10 in enabled cms pages'.PHP_EOL;

$magento = new Magento2\Client(
    $baseUrl,
    $username,
    $password
);


$enabledCmsPages = [];
$cmsPages = $magento->getCmsPages([
    [['is_active', 1, 'eq']],
    [['store_id', 3, 'in']],
]);
foreach($cmsPages['items'] as $cmsPage){
    echo $cmsPage['id'].' '.$cmsPage['identifier'].' '.$cmsPage['active'].PHP_EOL;
    $enabledCmsPages[$cmsPage['id']] = $cmsPage;
    if(count($enabledCmsPages) == 10){
        break;
    }
}

if(count($enabledCmsPages) < 10){
    $cmsPages = $magento->getCmsPages([
        [['is_active', 1, 'eq']],
        [['store_id', 0, 'in']],
    ]);
    foreach($cmsPages['items'] as $cmsPage){
        echo $cmsPage['id'].' '.$cmsPage['identifier'].' '.$cmsPage['active'].PHP_EOL;
        $enabledCmsPages[$cmsPage['id']] = $cmsPage;
        if(count($enabledCmsPages) == 10){
            break;
        }
    }
}

echo 'found categories: '.print_r(array_keys($enabledCmsPages), true).PHP_EOL;