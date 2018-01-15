#!/bin/bash

echo 'Composer install'
composer install
echo 'Npm install + grunt'
npm install
grunt default
echo 'Build docker images'
docker-composer down
docker-compose up -d
sleep 10

echo 'Install Magento'
docker-compose exec --user=root magento20 chown -R magento2:magento2 /var/www/ && \
docker-compose exec --user=magento2 magento20 /home/magento2/scripts/m2init magento:install --no-interaction --magento-host=magento20 && \

docker-compose exec --user=magento2 magento20 mkdir -p /var/www/magento2/app/code/DigitalOrigin && \

#docker-compose exec --user=magento2 magento20 ln -s /DigitalOrigin /var/www/magento2/app/code/DigitalOrigin/Pmt && \
#docker-compose exec --user=magento2 magento20 ln -s /DigitalOrigin /var/www/magento2/aplazame && \
#docker-compose exec --user=magento2 magento20 composer -d=/var/www/magento2/ require aplazame/aplazame-api-sdk && \
#docker-compose exec --user=magento2 magento20 php /var/www/magento2/bin/magento module:enable Aplazame_Payment && \
docker-compose exec --user=magento2 magento20 php /var/www/magento2/bin/magento sampledata:deploy && \
docker-compose exec --user=magento2 magento20 php /var/www/magento2/bin/magento setup:upgrade && \
docker-compose exec --user=magento2 magento20 php /var/www/magento2/bin/magento setup:di:compile && \
docker-compose exec --user=magento2 magento20 php /var/www/magento2/bin/magento cache:flush && \
docker-compose exec --user=magento2 magento20 php /var/www/magento2/bin/magento cache:warmup --magento-warm-up-storefront=1 --no-interaction
