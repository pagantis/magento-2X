<?php

use \Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Pagantis_Pagantis',
    isset($file) ? dirname($file) : __DIR__
);
