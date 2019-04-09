# Module installation <img src="https://camo.githubusercontent.com/758f1928ec7fd472d2183240199035f57ad95bfc/68747470733a2f2f646576656c6f7065722e706167616e7469732e636f6d2f6c6f676f732f706167616e7469735f7267625f636f6c6f722e706e67" width="100" align="right">

CicleCI: [![CircleCI](https://circleci.com/gh/pagantis/magento-2X/tree/master.svg?style=svg)](https://circleci.com/gh/pagantis/magento-2X/tree/master)

[![Latest Stable Version](https://poser.pugx.org/pagantis/magento-2x/v/stable)](https://packagist.org/packages/pagantis/magento-2x)
[![composer.lock](https://poser.pugx.org/pagantis/magento-2x/composerlock)](https://packagist.org/packages/pagantis/magento-2x)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/pagantis/magento-2x/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/pagantis/magento-2x/?branch=master)

## :hand: Requirements
This module supports Magento v2.1 and higher.

## :floppy_disk: Installation
To install the module of Pagantis in your Magento shop, you can use Composer:

```php
    composer require pagantis/magento-2x
    bin/magento module:enable Pagantis_Pagantis
    bin/magento setup:upgrade
    bin/magento setup:di:compile
    
    //remember to have the production mode enabled:
    bin/magento deploy:mode:set production
```

## :gear: Configuration
Configure the module in Magento admin panel using the information found in your [Pagantis profile](https://bo.pagantis.com/shop). 

For more information about how to config the module, see our [configuration section](/Documentation/configuration.md).

## :arrow_forward: Use
To use in a real environment you should enable your Pagantis account.

For more information about how to use the module, see our [use section](/Documentation/use.md).
