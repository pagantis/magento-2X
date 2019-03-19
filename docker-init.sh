#!/bin/bash
ENVIROMENT=$1
VERSION=$2

echo 'Build docker images'

if [ $1 == 'test' ]
then
    docker-compose up -d selenium
fi
echo "Executing command: docker-compose up -d --build magento"${VERSION}"-"${ENVIROMENT}
docker-compose up -d --build magento${VERSION}-${ENVIROMENT}
sleep 5
docker-compose exec magento${VERSION}-${ENVIROMENT} docker-php-ext-install bcmath

echo 'Install Magento'
docker-compose exec magento${VERSION}-${ENVIROMENT} install-magento

echo "Install Pagantis_Pagantis"
if [ $1 != 'test' ]
then
    docker-compose exec -u www-data magento${VERSION}-${ENVIROMENT} php /var/www/html/bin/magento \
        module:enable Pagantis_Pagantis --clear-static-content
    docker-compose exec -u www-data magento${VERSION}-${ENVIROMENT} composer install -d /var/www/html/app/code/Pagantis/Pagantis
else
    package="v7.0.8.x-dev"
    echo 'Package: '$package
    echo 'Running: composer require pagamastarde/magento-2x:'$package' -d /var/www/html'
    docker-compose exec -u www-data magento${VERSION}-${ENVIROMENT} composer require pagamastarde/magento-2x:$package -d /var/www/html
    echo 'Running: module:enable DigitalOrigin_Pmt'
    docker-compose exec -u www-data magento${VERSION}-${ENVIROMENT} \
        php /var/www/html/bin/magento module:enable DigitalOrigin_Pmt \
        --clear-static-content
fi

echo 'Sample Data + DI + SetupUpgrade + Clear Cache'
docker-compose exec -u www-data magento${VERSION}-${ENVIROMENT} composer config http-basic.repo.magento.com \
    5310458a34d580de1700dfe826ff19a1 \
    255059b03eb9d30604d5ef52fca7465d
echo 'Running: sampledata:deploy'
docker-compose exec -u www-data magento${VERSION}-${ENVIROMENT} php /var/www/html/bin/magento sampledata:deploy

echo 'Running: setup:upgrade'
docker-compose exec -u www-data magento${VERSION}-${ENVIROMENT} php /var/www/html/bin/magento setup:upgrade
echo 'Running: cron:run'
docker-compose exec -u www-data magento${VERSION}-${ENVIROMENT} php /var/www/html/bin/magento cron:run

if [ $1 == 'test' ]
then
    echo 'Running: cache:enable'
    docker-compose exec -u www-data magento${VERSION}-${ENVIROMENT} php /var/www/html/bin/magento cache:enable
else
    echo 'Running: cache:deploy:mode:set developer'
    docker-compose exec -u www-data magento${VERSION}-${ENVIROMENT} php /var/www/html/bin/magento deploy:mode:set developer
fi

containerPort=$(docker container port magento${VERSION}${ENVIROMENT})
PORT=$(sed  -e 's/.*://' <<< $containerPort)
echo 'Build of Magento2 complete: http://magento'${VERSION}'-'${ENVIROMENT}'.docker:'${PORT}

