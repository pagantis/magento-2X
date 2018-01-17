<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paylater\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Paylater\Gateway\Http\Client\ClientMock;

/**
 * Class ConfigProvider
 * @package Paylater\Model\Ui
 */
final class ConfigProvider implements ConfigProviderInterface
{
    /**
     * CODE constant
     */
    const CODE = 'sample_gateway';

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return array(
            'payment' => array(
                self::CODE => array(
                    'transactionResults' => array(
                        ClientMock::SUCCESS => __('Success'),
                        ClientMock::FAILURE => __('Fraud')
                    )
                )
            )
        );
    }
}
