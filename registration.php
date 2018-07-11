<?php

use \Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'DigitalOrigin_Pmt',
    isset($file) ? dirname($file) : __DIR__
);
