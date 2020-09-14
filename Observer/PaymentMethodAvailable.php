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
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $config        = $objectManager->create('Pagantis\Pagantis\Helper\Config')->getConfig();
                $extraConfig   = $objectManager->create('Pagantis\Pagantis\Helper\ExtraConfig')->getExtraConfig();
                $resolver      = $objectManager->create('Magento\Framework\Locale\Resolver');
                $locale = strstr($resolver->getLocale(), '_', true);
                $allowedCountries = unserialize($extraConfig['PAGANTIS_ALLOWED_COUNTRIES']);
                $availableCountry = (in_array(strtolower($locale), $allowedCountries));
                $maxAmount = $extraConfig['PAGANTIS_DISPLAY_MAX_AMOUNT'];
                $minAmount = $extraConfig['PAGANTIS_DISPLAY_MIN_AMOUNT'];
                $validAmount = ($totalPrice>=$minAmount && ($totalPrice<=$maxAmount||$maxAmount=='0'));
                $disabledPg = (!isset($config['pagantis_public_key']) || $config['pagantis_public_key'] == '' ||
                               !isset($config['pagantis_private_key']) || $config['pagantis_private_key'] == '' ||
                               !$availableCountry || !$validAmount || $config['active_12x']!=='1'
                               || $config['active']!=='1' );
                if ($disabledPg) {
                    $checkResult->setData('is_available', false);
                } else {
                    $checkResult->setData('is_available', true);
                }
            }

            if ($observer->getEvent()->getMethodInstance()->getCode()==ConfigProvider::CODE4X) {
                $checkResult = $observer->getEvent()->getResult();
                $totalPrice  = (string) floor($observer->getEvent()->getQuote()->getGrandTotal());
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $config        = $objectManager->create('Pagantis\Pagantis\Helper\Config')->getConfig();
                $extraConfig   = $objectManager->create('Pagantis\Pagantis\Helper\ExtraConfig')->getExtraConfig();
                $resolver      = $objectManager->create('Magento\Framework\Locale\Resolver');
                $locale = strstr($resolver->getLocale(), '_', true);
                $allowedCountries = unserialize($extraConfig['PAGANTIS_ALLOWED_COUNTRIES']);
                $availableCountry = (in_array(strtolower($locale), $allowedCountries));
                $maxAmount = $extraConfig['PAGANTIS_DISPLAY_MAX_AMOUNT_4x'];
                $minAmount = $extraConfig['PAGANTIS_DISPLAY_MIN_AMOUNT_4x'];
                $validAmount = ($totalPrice>=$minAmount && ($totalPrice<=$maxAmount||$maxAmount=='0'));
                $disabledPg4x = (!isset($config['pagantis_public_key_4x']) || $config['pagantis_public_key_4x']=='' ||
                                 !isset($config['pagantis_private_key_4x']) || $config['pagantis_private_key_4x']=='' ||
                                 !$availableCountry || !$validAmount || $config['active_4x']!=='1'
                                 || $config['active']!=='1' );
                if ($disabledPg4x) {
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
