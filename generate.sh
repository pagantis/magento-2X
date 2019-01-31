#!/bin/bash

# Prepare environment and build package
./docker-init.sh test $1
sleep 15
composer install --ignore-platform-reqs
set -e

# Run test
echo "running tests vendor/bin/phpunit --group magento-basic -d magentoVersion -d $1"
vendor/bin/phpunit --group magento-basic -d magentoVersion -d $1
echo "running tests vendor/bin/phpunit --group magento-install -d magentoVersion -d $1"
vendor/bin/phpunit --group magento-install  -d magentoVersion -d $1
echo "running tests vendor/bin/phpunit --group magento-buy-unregistered -d magentoVersion -d $1"
vendor/bin/phpunit --group magento-buy-unregistered  -d magentoVersion -d $1
echo "running tests vendor/bin/phpunit --group magento-register -d magentoVersion -d $1"
vendor/bin/phpunit --group magento-register  -d magentoVersion -d $1
echo "running tests vendor/bin/phpunit --group magento-buy-registered -d magentoVersion -d $1"
vendor/bin/phpunit --group magento-buy-registered  -d magentoVersion -d $1
