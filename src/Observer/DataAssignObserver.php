<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paylater\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;

/**
 * Class DataAssignObserver
 * @package Paylater\Observer
 */
class DataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * @param Observer $observer
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $method = $this->readMethodArgument($observer);
        $data = $this->readDataArgument($observer);

        $paymentInfo = $method->getInfoInstance();

        if ($data->getDataByKey('transaction_result') !== null) {
            $paymentInfo->setAdditionalInformation(
                'transaction_result',
                $data->getDataByKey('transaction_result')
            );
        }
    }
}
