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
 
- 1.0.5
   Added the methods:
  - getAllCategories($orderBy, $page, $limit)
  - getCustomer($customer_id)
  - getOrder($order_id)
  - getOrders($where, $orderBy, $page, $limit)
  - getProductBySku($sku)
  - setStockLevelForSku(($sku, $quantity, $item_id)
  
- 1.0.6
  Added the methods:
  - getStockStatuses($sku)
  - shipOrder($orderId, $items = [], $notify = true, $appendComment = true, $comment = [], $tracks = [], $packages = [], $arguments = [])
  - getOrderItems($where = [], $orderBy = null, $page = 1, $limit = 100)
  - Added the ability to supply custom configuration to the guzzle client

- 1.0.7
   - Bumped Guzzle version to support newer apps.

- 1.0.10
  Replaced attribute methods:
    - getProductAttributeOptions($attribute = 'string')
    - getProductAttribute($attribute = 'string')
    - addProductAttributeLabel($attribute = 'string', $label = 'string')
    - deleteProductAttributeLabel($attribute = 'string', int $label_id)

- 1.0.18
  - Refactor to allow use with custom endpoints, see [example11](/examples/example11.php)
  
- 1.0.19
  - Addition of "entity not found" exception, see [example12](/examples/example12.php)
  
- 1.0.20
  - Added update product example
  - Added get product attribute options example
  - Added update product function

- 1.1.0
  - Added ability to pass in token manager, see [example15](/examples/example15.php)
  - bug fix: getAllCategories will now recursively get all categories instead of just the first page
  - bug fix: getCategories invalid url endpoint resolved
  - feature: getProductAttributeOptions can now have a store code passed in
  - feature: addProductAttributeLabel can now be passed a list of "store labels" which should be added to the new attribute option
  - bug fix: setWebsiteForProduct invalid url endpoint resolved
  - feature: Tier prices can now be managed through: getTierPrices, removeTierPrices, setTierPrices
  - feature: products can now be added to categories via addProductToCategory
  - feature: media gallery entries can now be managed through: getMediaGalleryEntries, addMediaGalleryEntry, updateMediaGalleryEntry, deleteMediaGalleryEntry
