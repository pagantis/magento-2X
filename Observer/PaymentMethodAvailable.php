<?php

namespace DigitalOrigin\Pmt\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class PaymentMethodAvailable
 * @package DigitalOrigin\Pmt\Observer
 */
class PaymentMethodAvailable implements ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            if ($observer->getEvent()->getMethodInstance()->getCode()=="paylater") {
                $checkResult = $observer->getEvent()->getResult();
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $config        = $objectManager->create('DigitalOrigin\Pmt\Helper\Config')->getConfig();
                if (!isset($config['pmt_public_key']) || $config['pmt_public_key'] == '' ||
                    !isset($config['pmt_private_key']) || $config['pmt_private_key'] == '') {
                    $checkResult->setData('is_available', false);
                } else {
                    $checkResult->setData('is_available', true);
                }
            }
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }
}
