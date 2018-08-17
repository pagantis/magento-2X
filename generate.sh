#!/bin/bash

# Prepare environment and build package
./docker-init.sh test
docker cp magento-2x_magento2-test_1:/var/www/.composer/auth.json .
composer install --ignore-platform-reqs
set -e

# Run test
vendor/bin/phpunit --group magento-basic
vendor/bin/phpunit --group magento-install
vendor/bin/phpunit --group magento-buy-unregistered
vendor/bin/phpunit --group magento-register
vendor/bin/phpunit --group magento-buy-registered