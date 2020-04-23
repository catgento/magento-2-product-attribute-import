# Magento Product Attribute Importer for Magento 2 >= 2.1.x

This is a Magento module which adds a new command (import:productattribute) to the bin/magento shell to import product attributes given a SKU

## Installation
### Composer
```
    composer require catgento/magento-2-product-attribute-import
```
### ZIP file
Download the module and unzip it under the folder app/code/Catgento/ProductAttributeImport.  

## How to use it

### Create a CSV file with the product information

Mandatory columns:

```
sku
```

Extra columns (you can add as many as you want as long as they are product attributes). Note that the first row column values will be used for setting the product data:

```
name
description
url_key
url_path
...
```

Sample file:
```
sku,url_key,url_path
1000,white-mug,white-mug.html
1119,mug-with-calendar,mug-with-calendar.html
1001,mug-with-inner-colour,mug-with-inner-colour.html
```


### Import the CSV file in Magento 2

You can use this shell command as follows:

```
bin/magento import:productattribute -p var/import/product-attributes.csv 	
```
