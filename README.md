# Magento 2X Module installation <img src="https://developer.pagantis.com/logos/pagantis_rgb_color.png" width="100" align="right">

CicleCI: [![CircleCI](https://circleci.com/gh/pagantis/magento-2X/tree/master.svg?style=svg)](https://circleci.com/gh/pagantis/magento-2X/tree/master)

<!--
[![Latest Stable Version](https://poser.pugx.org/pagantis/magento-2x/v/stable)](https://packagist.org/packages/pagantis/magento-2x)
[![composer.lock](https://poser.pugx.org/pagantis/magento-2x/composerlock)](https://packagist.org/packages/pagantis/magento-2x)
-->
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/pagantis/magento-2x/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/pagantis/magento-2x/?branch=master)

## :hand: Requirements
 * Magento v2.1 and higher.
 * Composer

## :floppy_disk: Installation
To install the Pagantis module in your Magento shop,you can use Composer  :  
```shell
composer require pagantis/magento-2x
bin/magento module:enable Pagantis_Pagantis
bin/magento setup:upgrade
bin/magento setup:di:compile
```

## :gear: Configuration
Configure the plugin in Magento admin panel admin panel using the information found in your [Pagantis profile](https://bo.pagantis.com/shop) and our [configuration section](/Documentation/configuration.md).

## :arrow_forward: Usage
To use in a real environment your Pagantis account should be enabled accordingly.

For more information about how to use the module, see our [usage section](/Documentation/usage.md).
