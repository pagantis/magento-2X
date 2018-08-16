#!/bin/bash

# Prepare environment and build package
./docker-init.sh test

# Time to boot and install magento
#sleep 660
set -e

# Run test
vendor/bin/phpunit --group magento-basic
vendor/bin/phpunit --group magento-install
vendor/bin/phpunit --group magento-buy-unregistered
vendor/bin/phpunit --group magento-register
vendor/bin/phpunit --group magento-buy-registered