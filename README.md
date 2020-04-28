# magento2-rest-client
PHP rest client for Magento 2

## Examples
More examples can be found in the [examples](/examples) directory.
```php
$magento = new Magento2\Client(
    'https://www.mysite.com',
    'username',
    'password'
);

$products = $magento->getProducts([
    [['type_id', 'simple', 'eq']],
    [['status', 1, 'eq']],
    [['visibility', 1, 'gt']]
]);
```
_get all simple, enabled and visible products_

## Changelog
- 1.0.0  
  Initial commit
- 1.0.1  
  Added the methods:
  - getStockItem
  - getCategories
  - getStoreViews
  - getStoreGroups
  - getCmsPages
- 1.0.2
  - Added method getStoreConfiguration
  - added to docs
  - moved examples
  - added example
  
- 1.0.3
  Added the methods:
  - getProductBySku($sku)
  - setStockLevelForSku($sku, $quantity, $item_id)  
  - loadOrder($id)
  - getOrders($filter)
  - getCustomer($id)
  - addOrderComment($orderId, $status, $message, $notify)
  - getAllProducts($filters)
  - getStockInfo($sku_or_product_id)
  - updateStock($sku_or_id, $stockdata)
  - getAllGiftCards
  - updateGiftCard($code, $balance, $expiry = null)
  - getGiftCardFromOrder($order_id)
  