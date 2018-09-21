# Módulo Magento 2X <img src="https://pagamastarde.com/img/icons/logo.svg" width="100" align="right">

[![Build Status](https://travis-ci.org/PagaMasTarde/magento-2X.svg?branch=master)](https://travis-ci.org/PagaMasTarde/magento-2X)
[![Latest Stable Version](https://poser.pugx.org/pagamastarde/magento-2x/v/stable)](https://packagist.org/packages/pagamastarde/magento-2x)
[![composer.lock](https://poser.pugx.org/pagamastarde/magento-2x/composerlock)](https://packagist.org/packages/pagamastarde/magento-2x)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/PagaMasTarde/magento-2x/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/PagaMasTarde/magento-2x/?branch=master)

## Requisitos
Este módulo se puede usar a partir de Magento 2.1 y versiones superiores

## Instrucciones de Instalación
- Crea tu cuenta en pagamastarde.com si aún no la tienes [desde aquí](https://bo.pagamastarde.com/users/sign_up)
- Instala el módulo en tu tienda Magento mediante Composer usando los siguientes comandos:
```php
    composer require pagamastarde/magento-2x
    bin/magento module:enable DigitalOrigin_Pmt
    bin/magento setup:upgrade
```
- Configura el módulo con la información de tu cuenta que encontrarás en [el panel de gestión de Paga+Tarde](https://bo.pagamastarde.com/shop). Ten en cuenta que para hacer cobros reales deberás activar tu cuenta de Paga+Tarde.

## Modo real y modo de pruebas

Tanto el módulo como Paga+Tarde tienen funcionamiento en real y en modo de pruebas independientes. Debes introducir las credenciales correspondientes del entorno que desees usar.

### Soporte

Si tienes alguna duda o pregunta no tienes más que escribirnos un email a [soporte@pagamastarde.com]

## Instrucciones para desarrollo:

Para colaborar o mejorar este módulo, necesitas tener instalado en tu entorno Docker y docker-compose
    
Para activar el módulo se necesita descargar las dependencias: 

    ./docker-init.sh dev


### Documentation

[Documentación Magento 2.x en Paga+Tarde](https://docs.pagamastarde.com/modecommerce/magento/)

