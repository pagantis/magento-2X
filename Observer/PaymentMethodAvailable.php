<?php

namespace Pagantis\Pagantis\Observer;

use Magento\Framework\Event\ObserverInterface;
use Pagantis\Pagantis\Model\Ui\ConfigProvider;


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
            if ($observer->getEvent()->getMethodInstance()->getCode()==ConfigProvider::CODE) {
                $checkResult = $observer->getEvent()->getResult();
                $totalPrice  = (string) floor($observer->getEvent()->getQuote()->getGrandTotal());
                $totalPrice  = (float)$observer->getEvent()->getQuote()->getGrandTotal();
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $config        = $objectManager->create('Pagantis\Pagantis\Helper\Config')->getConfig();
                $extraConfig   = $objectManager->create('Pagantis\Pagantis\Helper\ExtraConfig')->getExtraConfig();
                $resolver      = $objectManager->create('Magento\Framework\Locale\Resolver');
                $locale = strstr($resolver->getLocale(), '_', true);
                $allowedCountries = unserialize($extraConfig['PAGANTIS_ALLOWED_COUNTRIES']);
                $availableCountry = (in_array(strtolower($locale), $allowedCountries));
                $maxAmount = $config['clearpay_max_amount'];
                $minAmount = $config['clearpay_min_amount'];
                $validAmount = ($totalPrice>=$minAmount && $totalPrice<=$maxAmount);
                $disabledPg = (!isset($config['clearpay_merchant_id']) || $config['clearpay_merchant_id'] == '' ||
                               !isset($config['clearpay_merchant_key']) || $config['clearpay_merchant_key'] == ''
                               || !$availableCountry || $config['active']!=='1' || !$validAmount);

                if ($disabledPg) {
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
