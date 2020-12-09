<?php

namespace Clearpay\Clearpay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Clearpay\Clearpay\Helper\Category;
use Clearpay\Clearpay\Model\Ui\ConfigProvider;

/**
 * Class PaymentMethodAvailable
 * @package Clearpay\Clearpay\Observer
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
                $totalPrice  = (float)$observer->getEvent()->getQuote()->getGrandTotal();
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $config        = $objectManager->create('Clearpay\Clearpay\Helper\Config')->getConfig();
                $extraConfig   = $objectManager->create('Clearpay\Clearpay\Helper\ExtraConfig')->getExtraConfig();
                $resolver      = $objectManager->create('Magento\Framework\Locale\Resolver');
                $category      = $objectManager->create('Clearpay\Clearpay\Helper\Category');

                if ($config['clearpay_api_region'] === 'GB') {
                    $allowedCountries = array('0'=>'gb');
                } else {
                    $allowedCountries = unserialize($extraConfig['CLEARPAY_ALLOWED_COUNTRIES']);
                }
                $locale = strstr($resolver->getLocale(), '_', true);
                $availableCountry = (in_array(strtolower($locale), $allowedCountries));
                $maxAmount = $config['clearpay_max_amount'];
                $minAmount = $config['clearpay_min_amount'];
                $validAmount = ($totalPrice>=$minAmount && $totalPrice<=$maxAmount);
                $itemArray = $this->getItemArray($observer->getEvent()->getQuote());
                $checkProductCategories = $category->allowedCategories($itemArray);
                $disabledCp = (!isset($config['clearpay_merchant_id']) || $config['clearpay_merchant_id'] == '' ||
                               !isset($config['clearpay_merchant_key']) || $config['clearpay_merchant_key'] == ''
                               || !$availableCountry || $config['active']!=='1' || !$validAmount
                               || !empty($checkProductCategories));

                if ($disabledCp) {
                    $checkResult->setData('is_available', false);
                } else {
                    $checkResult->setData('is_available', true);
                }
            }
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * @param $quote
     *
     * @return array
     */
    private function getItemArray($quote)
    {
        $items     = $quote->getAllVisibleItems();
        $itemArray = array();
        foreach ($items as $key => $item) {
            $itemArray[] = $item;
        }

        return $itemArray;
    }

}
