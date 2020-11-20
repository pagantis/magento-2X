<?php

namespace Pagantis\Pagantis\Block\Product;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\StoreManager;
use Pagantis\Pagantis\Helper\MerchantProperties;

/**
 * Class Simulator
 * @package Pagantis\Pagantis\Block\Product
 */
class Simulator extends Template
{
    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var String
     */
    protected $store;

    /**
     * @var float $amount
     */
    protected $amount;

    /**
     * @var string|null $locale
     */
    protected $locale;

    /**
     * @var string|null $minAmount
     */
    protected $minAmount;

    /**
     * @var string|null $maxAmount
     */
    protected $maxAmount;

    /**
     * @var string|null $currency
     */
    protected $currency;

    /**
     * Simulator constructor.
     *
     * @param Context      $context
     * @param Registry     $registry
     * @param Resolver     $store
     * @param StoreManager $storeManager
     * @param array        $data
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Resolver $store,
        StoreManager $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->store = $store;
        /** @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig */
        $scopeConfig = $this->_scopeConfig;
        $config = $scopeConfig->getValue('payment/pagantis');

        $this->product = $this->registry->registry('product');
        $this->enabled = $config['active'];
        $this->amount = number_format($this->product->getFinalPrice(), 2);
        $this->locale = $this->store->getLocale();
        $this->currency = $storeManager->getStore()->getCurrentCurrencyCode();
        $this->minAmount = isset($config['clearpay_min_amount']) ?
            $config['clearpay_min_amount'] : MerchantProperties::DEF_MIN_AMOUNT;
        $this->maxAmount = isset($config['clearpay_max_amount']) ?
            $config['clearpay_max_amount'] : MerchantProperties::DEF_MIN_AMOUNT;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getEnabled();
    }

    //GETTERS AND SETTERS

    /**
     * @return string|null
     */
    public function getMinAmount()
    {
        return $this->minAmount;
    }

    /**
     * @param $minAmount
     */
    public function setMinAmount($minAmount)
    {
        $this->minAmount = $minAmount;
    }

    /**
     * @return string|null
     */
    public function getMaxAmount()
    {
        return $this->maxAmount;
    }

    /**
     * @param $maxAmount
     */
    public function setMaxAmount($maxAmount)
    {
        $this->maxAmount = $maxAmount;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     */
    public function setProduct($product)
    {
        $this->product = $product;
    }

    /**
     * @return Registry
     */
    public function getRegistry()
    {
        return $this->registry;
    }

    /**
     * @param Registry $registry
     */
    public function setRegistry($registry)
    {
        $this->registry = $registry;
    }

    /**
     * @return String
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * @param String $store
     */
    public function setStore($store)
    {
        $this->store = $store;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return string|null
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string|null $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return string|null
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string|null $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }
}
