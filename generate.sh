#!/bin/bash

while true; do
    read -p "Do you wish to run dev or test [test|dev]? " environment
    case $environment in
        [dev]* ) break;;
        [test]* ) break;;
        * ) echo "Please answer dev or test.";;
    esac
done
while true; do
    read -p "Do you wish to run version 2.2, 2.3 or 2.4 [22|23|24]? " version
    case $version in
        [22]* ) break;;
        [23]* ) break;;
        [24]* ) break;;
        * ) echo "Please answer 22, 23 or 24.";;
    esac
done

container="magento$version-$environment"
while true; do
    read -p "You have chosen to start ${container}, are you sure [y/n]? " yn
    case $yn in
        [Yy]* ) break;;
        [Nn]* ) exit;;
        * ) echo "Please answer yes or no.";;
    esac
done



docker-compose down
docker ps -aq --no-trunc -f status=exited | xargs docker rm
docker-compose up -d selenium
docker-compose up -d --build ${container}
docker-compose exec ${container} docker-php-ext-install bcmath
docker-compose exec ${container} install-magento

if [ $environment = "dev" ]
then
    docker-compose exec -u www-data ${container} php /var/www/html/bin/magento \
        module:enable Pagantis_Pagantis --clear-static-content
    docker-compose exec -u www-data ${container} composer install -d /var/www/html/app/code/Pagantis/Pagantis
    docker-compose exec -u www-data ${container} composer require "pagantis/orders-api-client"
    docker-compose exec -u www-data ${container} composer require "pagantis/module-utils"
else
    package='dev-xxx'
    echo 'Package: '$package
    docker-compose exec -u www-data ${container} composer require pagantis/magento-2x:$package -d /var/www/html
    docker-compose exec -u www-data ${container} \
        php /var/www/html/bin/magento module:enable Pagantis_Pagantis --clear-static-content
    docker-compose exec -u www-data ${container} \
        php /var/www/html/bin/magento setup:upgrade
    docker-compose exec -u www-data ${container} \
        php /var/www/html/bin/magento setup:di:compile
    docker-compose exec -u www-data ${container} \
        php /var/www/html/bin/magento cache:flush
fi

docker-compose exec -u www-data ${container} composer config http-basic.repo.magento.com \
    5310458a34d580de1700dfe826ff19a1 \
    255059b03eb9d30604d5ef52fca7465d
docker-compose exec -u www-data ${container} php /var/www/html/bin/magento sampledata:deploy
docker-compose exec -u www-data ${container} php /var/www/html/bin/magento setup:upgrade
docker-compose exec -u www-data ${container} php /var/www/html/bin/magento cron:run
docker-compose exec -u www-data ${container} php /var/www/html/bin/magento deploy:mode:set developer
docker-compose exec -u www-data ${container} php /var/www/html/bin/magento cache:flush

if [ $environment = "dev" ]
then
    docker-compose exec -u www-data ${container} php /var/www/html/bin/magento maintenance:disable
fi

    while true; do
        read -p "Do you want to run full tests battery or only configure the module [full/install/none]? " tests
        case $tests in
            [full]* ) break;;
            [configure]* ) break;;
            [none]* ) break;;
            * ) echo "Please answer full, configure or none."; exit;;
        esac
    done

if [ ! -z "$tests" ] && [ "$tests" != "none" ];
then
    vendor/bin/phpunit --group magento-basic -d magentoVersion -d ${version} -d ${environment}

    #Only for TEST environment. DEV environment is already installed
    if [ $environment = "test" ]
    then
        vendor/bin/phpunit --group magento-install -d magentoVersion -d ${version} -d ${environment}
    else
        vendor/bin/phpunit --group magento-register -d magentoVersion -d ${version} -d ${environment}
    fi

    if [ $tests = "full" ]
    then
        vendor/bin/phpunit --group magento-buy-unregistered -d magentoVersion -d ${version} -d ${environment}
        vendor/bin/phpunit --group magento-register -d magentoVersion -d ${version} -d ${environment}
        vendor/bin/phpunit --group magento-buy-registered -d magentoVersion -d ${version} -d ${environment}
    fi
fi

containerPort=$(docker container port ${container})
PORT=$(sed  -e 's/.*://' <<< $containerPort)
echo 'Build of Magento 2 complete: http://'${container}'.docker:'${PORT}
