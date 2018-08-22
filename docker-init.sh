#!/bin/bash
ENVIROMENT=$1
echo 'Build docker images'
docker-compose down
docker-compose up -d --build magento2-${ENVIROMENT}
if [ $1 == 'dev' ]
then
docker-compose up -d phpmyadmin
fi
docker-compose up -d selenium
sleep 10

echo 'Install Magento'
docker-compose exec magento2-${ENVIROMENT} install-magento
echo 'Install DigitalOrigin_Pmt'
if [ $1 == 'dev' ]
then
docker-compose exec -u www-data magento2-${ENVIROMENT} mkdir -p /var/www/html/app/code/DigitalOrigin && \
docker-compose exec -u www-data magento2-${ENVIROMENT} ln -s /var/www/paylater /var/www/html/app/code/DigitalOrigin/Pmt && \
docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento module:enable DigitalOrigin_Pmt && \
docker-compose exec magento2-${ENVIROMENT} chown -R www-data. /var/www/paylater
docker-compose exec -u www-data magento2-${ENVIROMENT} composer install -d /var/www/paylater
docker-compose exec -u www-data magento2-${ENVIROMENT} composer require pagamastarde/orders-api-client -d /var/www/html
docker-compose exec -u www-data magento2-${ENVIROMENT} composer require pagamastarde/selenium-form-utils -d /var/www/html
else
docker-compose exec -u www-data magento2-${ENVIROMENT} composer require pagamastarde/magento-2x:$(git describe --contains --all HEAD).x-dev -d /var/www/html
docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento module:enable DigitalOrigin_Pmt
docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento setup:upgrade
fi

echo 'Sample Data + DI + SetupUpgrade + Clear Cache'
docker-compose exec magento2-${ENVIROMENT} install-sampledata
docker-compose exec -u www-data magento2-${ENVIROMENT} /var/www/html/bin/magento cron:run
echo 'Build of Magento2 enviroment complete: http://magento2.docker:8086'
