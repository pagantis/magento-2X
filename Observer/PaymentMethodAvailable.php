<?php

namespace Pagantis\Pagantis\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class PaymentMethodAvailable
 * @package Pagantis\Pagantis\Observer
 */
class PaymentMethodAvailable implements ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            if ($observer->getEvent()->getMethodInstance()->getCode()=="pagantis") {
                $checkResult = $observer->getEvent()->getResult();
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $config        = $objectManager->create('Pagantis\Pagantis\Helper\Config')->getConfig();
                if (!isset($config['pagantis_public_key']) || $config['pagantis_public_key'] == '' ||
                    !isset($config['pagantis_private_key']) || $config['pagantis_private_key'] == '') {
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
