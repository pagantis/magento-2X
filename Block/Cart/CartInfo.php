<?php

namespace Clearpay\Clearpay\Block\Cart;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class CartInfo
 * @package Clearpay\Clearpay\Block\Cart
 */
class CartInfo extends Template
{
    /** @var Quote $quote */
    protected $quote;

    /** @var mixed $dividedTotal */
    protected $dividedTotal;

    /** @var mixed $currencySymbol */
    protected $currencySymbol;

    /** @var StoreInterface $store */
    protected $store;

    /** @var \Magento\Framework\App\ObjectManager $objectManager */
    protected $objectManager;

    /**
     * CartInfo constructor.
     *
     * @param Context        $context
     * @param StoreInterface $storeInterface
     * @param array          $data
     */
    public function __construct(
        Context $context,
        StoreInterface $storeInterface,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->quote = $this->objectManager->get('\Magento\Checkout\Model\Cart')->getQuote();

        $this->dividedTotal = number_format($this->quote->getGrandTotal()/4, 2);
        $this->currencySymbol = $this->objectManager
            ->create('Magento\Directory\Model\CurrencyFactory')
            ->create()
            ->load($this->quote->getCurrency()->getBaseCurrencyCode())
            ->getCurrencySymbol();
        $this->store = $storeInterface;
    }

    public function getPriceText()
    {
        $fullPrice = $this->dividedTotal.$this->currencySymbol;

        return sprintf(__('4 interest-free payments of %s'), $fullPrice);
    }

    public function getDescription1()
    {
        return __('With Clearpay you can receive your order now and pay in 4 interest-free equal fortnightly payments.').
               __('Available to customers in the United Kingdom with a debit or credit card.');
    }

    public function getDescription2()
    {
        return 'When you click ”Checkout with Clearpay" you will be redirected to Clearpay to complete your order.';
    }

    public function getCountryCode()
    {
        return ($this->store->getLocale()!=null) ? $this->store->getLocale() : $this->getResolverCountry();
    }

    public function getMoreInfo()
    {
        return __('FIND OUT MORE');
    }

    /**
     * @return mixed
     */
    private function getResolverCountry()
    {
        $store = $this->objectManager->get('Magento\Framework\Locale\Resolver');

        if (method_exists($store, 'getLocale')) {
            return $store->getLocale();
        }

        return null;
    }

}