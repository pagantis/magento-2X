#!/bin/bash

# Prepare environment and build package
./docker-init.sh test
sleep 15
docker cp magento2test:/var/www/.composer/auth.json .
composer install --ignore-platform-reqs
set -e

# Run test
vendor/bin/phpunit --group magento-basic
vendor/bin/phpunit --group magento-install
vendor/bin/phpunit --group magento-buy-unregistered
vendor/bin/phpunit --group magento-register
vendor/bin/phpunit --group magento-buy-registered
