#!/bin/bash
ENVIROMENT=$1
echo 'Build docker images'
docker-compose down
docker-compose up -d --build magento2-${ENVIROMENT}
docker-compose up -d selenium
sleep 10

echo 'Install Magento'
docker-compose exec magento2-${ENVIROMENT} install-magento
echo 'Install DigitalOrigin_Pmt'
if [ $1 == 'dev' ]
then
    PORT='8086'
    docker-compose exec -u www-data magento2-${ENVIROMENT} mkdir -p /var/www/html/app/code/DigitalOrigin && \
    docker-compose exec -u www-data magento2-${ENVIROMENT} ln -s /var/www/paylater /var/www/html/app/code/DigitalOrigin/Pmt && \
    docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento module:enable DigitalOrigin_Pmt && \
    docker-compose exec magento2-${ENVIROMENT} chown -R www-data. /var/www/paylater
    docker-compose exec -u www-data magento2-${ENVIROMENT} composer install -d /var/www/paylater
else
    PORT='8085'
    version=$(git describe --all HEAD)
    versionParsed=$(sed  -e 's/heads\///' -e 's/-.*//' <<< $version)
    package=$versionParsed'.x-dev'
    if [ $package == 'master.x-dev' ]
    then
        package='dev-master'
    fi
    echo "Esta es la rama del pull request" ${TRAVIS_PULL_REQUEST_BRANCH}
    if [ ${TRAVIS_PULL_REQUEST_BRANCH} != '' ]
    then
        package=${TRAVIS_PULL_REQUEST_BRANCH}'.x-dev'
    fi
    echo 'Package: '$package
    docker-compose exec -u www-data magento2-${ENVIROMENT} composer require pagamastarde/magento-2x:$package -d /var/www/html
    docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento module:enable DigitalOrigin_Pmt
    docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento setup:upgrade
fi

echo 'Sample Data + DI + SetupUpgrade + Clear Cache'
docker-compose exec magento2-${ENVIROMENT} install-sampledata
docker-compose exec -u www-data magento2-${ENVIROMENT} /var/www/html/bin/magento cron:run
docker container port magento2-${ENVIROMENT}
echo 'Build of Magento2 complete: http://magento2-'${ENVIROMENT}'.docker:'${PORT}
