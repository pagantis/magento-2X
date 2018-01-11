# Magento 1X Module <img src="https://pagamastarde.com/img/icons/logo.svg" width="100" align="right">

[![Build Status](https://travis-ci.org/PagaMasTarde/magento-1X.svg?branch=master)](https://travis-ci.org/PagaMasTarde/magento-1X)
[![Latest Stable Version](https://poser.pugx.org/pagamastarde/magento-1x/v/stable)](https://packagist.org/packages/pagamastarde/magento-1x)
[![composer.lock](https://poser.pugx.org/pagamastarde/magento-1x/composerlock)](https://packagist.org/packages/pagamastarde/magento-1x)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/4f04d66a-b31f-436d-a15e-bf27fa8164a1/mini.png)](https://insight.sensiolabs.com/projects/4f04d66a-b31f-436d-a15e-bf27fa8164a1)

## Instrucciones de Instalación
1. Crea tu cuenta en pagamastarde.com si aún no la tienes [desde aquí](https://bo.pagamastarde.com/users/sign_up)
2. Descarga el módulo de [aquí](https://github.com/pagamastarde/magento-1X/releases/latest)
3. Instala el módulo en tu magento
4. Configuralo con la información de tu cuenta que encontrarás en [el panel de gestión de Paga+Tarde](https://bo.pagamastarde.com/shop). Ten en cuenta que para hacer cobros reales deberás activar tu cuenta de Paga+Tarde.

## Modo real y modo de pruebas

Tanto el módulo como Paga+Tarde tienen funcionamiento en real y en modo de pruebas independientes. Debes introducir las credenciales correspondientes del entorno que desees usar.

### Soporte

Si tienes alguna duda o pregunta no tienes más que escribirnos un email a [soporte@pagamastarde.com]

## Development Instructions:

To develop or improve this module you need to have installed in your environment
    * Composer
    
To make the module operative you need to download the dependencies, 

    composer install
    
Once both dependencies are ready you can generate the specific module files using

    grunt default
    
Grunt will compress the CSS and the JS and generate a zip file with the necessary files to push
to the market.

You can always do a symbolic link from your local installation of prestashop in order to verify
the functionality of the code.


### Testing and Improvements

* Doing some phpUnit testing on the module.
* Improving the code structure to make it more human.

