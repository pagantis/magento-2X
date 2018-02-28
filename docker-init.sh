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
docker-compose exec magento20 install-magento
echo 'Install DigitalOrigin_Pmt'
docker-compose exec magento20 mkdir -p /var/www/html/app/code/DigitalOrigin && \
docker-compose exec magento20 ln -s /var/www/paylater /var/www/html/app/code/DigitalOrigin/Pmt && \
docker-compose exec magento20 php /var/www/html/bin/magento module:enable DigitalOrigin_Pmt && \
echo 'Sample Data + DI + SetupUpgrade + Clear Cache'
docker-compose exec magento20 install-sampledata
docker-compose exec magento20 php /var/www/magento2/bin/magento cache:flush
echo 'Build of Magento2 enviroment complete: http://magento20:8086'