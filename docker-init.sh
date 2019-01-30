#!/bin/bash
ENVIROMENT=$1

echo 'Build docker images'

if [ $1 == 'test' ]
then
    docker-compose down
    docker-compose up -d selenium
fi
docker-compose up -d --build magento2-${ENVIROMENT}
sleep 5
docker-compose exec magento2-${ENVIROMENT} docker-php-ext-install bcmath

echo 'Install Magento'
docker-compose exec magento2-${ENVIROMENT} install-magento

echo 'Sample Data + DI + SetupUpgrade + Clear Cache'
docker-compose exec -u www-data magento2-${ENVIROMENT} composer config http-basic.repo.magento.com \
    5310458a34d580de1700dfe826ff19a1 \
    255059b03eb9d30604d5ef52fca7465d
echo 'Running: sampledata:deploy'
docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento sampledata:deploy
echo 'Running: setup:upgrade'
docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento setup:upgrade

echo 'Install DigitalOrigin_Pmt'
if [ $1 != 'test' ]
then
    docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento \
        module:enable DigitalOrigin_Pmt --clear-static-content
    docker-compose exec -u www-data magento2-${ENVIROMENT} composer install -d /var/www/html/app/code/Digita:lOrigin/Pmt
else
    package='dev-master'
    if [ ! -z "$TRAVIS_PULL_REQUEST_BRANCH" ]
    then
        echo "This is the branch of the pull request" ${TRAVIS_PULL_REQUEST_BRANCH}
        package=${TRAVIS_PULL_REQUEST_BRANCH}'.x-dev'
    fi

    if [ ! -z "$TRAVIS_TAG" ]
    then
        echo "This is the branch of the tag:" ${TRAVIS_TAG}
        package=${TRAVIS_TAG}
    fi
    if [ ! -z "$TRAVIS_BRANCH" ]
    then
        echo "This is the branch of the branch:" ${TRAVIS_BRANCH}
        package=${TRAVIS_BRANCH}'.x-dev'
    fi
    package='v7.0.8.x-dev'
    echo 'Package: '$package

    echo 'Running: composer requiere pagamastarde/magento-2x:'$package' -d /var/www/html'
    docker-compose exec -u www-data magento2-${ENVIROMENT} composer require pagamastarde/magento-2x:$package \
        -d /var/www/html
    echo 'Running: module:enable DigitalOrigin_Pmt'
    docker-compose exec -u www-data magento2-${ENVIROMENT} \
        php /var/www/html/bin/magento module:enable DigitalOrigin_Pmt \
        --clear-static-content
fi


if [ $1 == 'test' ]
then
    echo 'Running: cron:run'
    docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento cron:run
    echo 'Running: chmod 777 -R generated'
    docker-compose exec magento2-${ENVIROMENT} chmod 777 -R generated
    echo 'Running: chmod 777 -R var/cache'
    docker-compose exec magento2-${ENVIROMENT} chmod 777 -R var/cache
    echo 'Running: setup:di:compile'
    docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento setup:di:compile
    echo 'Running: cache:enable'
    docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento cache:enable
    echo 'Running: cache:deploy:mode:set production'
    docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento deploy:mode:set production
else
    echo 'Running: cache:deploy:mode:set developer'
    docker-compose exec -u www-data magento2-${ENVIROMENT} php /var/www/html/bin/magento deploy:mode:set developer
fi

containerPort=$(docker container port magento2${ENVIROMENT})
PORT=$(sed  -e 's/.*://' <<< $containerPort)
echo 'Build of Magento2 complete: http://magento2-'${ENVIROMENT}'.docker:'${PORT}
