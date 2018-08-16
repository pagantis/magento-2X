#!/bin/bash

# Prepare environment and build package
./docker-init.sh test

# Time to boot and install magento
#sleep 660
set -e

# Run test
docker-compose exec magento2-test vendor/bin/phpunit --group magento-basic
docker-compose exec magento2-test vendor/bin/phpunit --group magento-install
docker-compose exec magento2-test vendor/bin/phpunit --group magento-buy-unregistered
docker-compose exec magento2-test vendor/bin/phpunit --group magento-register
docker-compose exec magento2-test vendor/bin/phpunit --group magento-buy-registered