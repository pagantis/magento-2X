#!/bin/bash

echo 'Composer install'
composer install
echo 'Npm install + grunt'
npm install
grunt default
echo 'Build docker images'
docker-compose down
docker-compose up -d
sleep 10

echo 'Install Magento'
docker-compose exec magento2 install-magento
echo 'Install DigitalOrigin_Pmt'
docker-compose exec --user=www-data magento2 mkdir -p /var/www/html/app/code/DigitalOrigin && \
docker-compose exec --user=www-data magento2 ln -s /var/www/paylater /var/www/html/app/code/DigitalOrigin/Pmt && \
docker-compose exec --user=www-data magento2 php /var/www/html/bin/magento module:enable DigitalOrigin_Pmt && \
echo 'Sample Data + DI + SetupUpgrade + Clear Cache'
docker-compose exec magento2 install-sampledata
echo 'Build of Magento2 enviroment complete: http://magento2:8086'