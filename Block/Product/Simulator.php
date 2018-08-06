<?php

namespace DigitalOrigin\Pmt\Block\Product;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;

/**
 * Class Simulator
 * @package DigitalOrigin\Pmt\Block\Product
 */
class Simulator extends Template
{
    const PROMOTIONS_CATEGORY = 'paylater-promotion-product';

    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @var string
     */
    protected $publicKey;

    /**
     * @var int
     */
    protected $productSimulator;

    /**
     * @var string
     */
    protected $promotionProductExtra;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var float
     */
    protected $minAmount;

    /**
     * @var float
     */
    protected $maxAmount;

    /**
     * @var int
     */
    protected $minInstallments;

    /**
     * @var int
     */
    protected $maxInstallments;

    /**
     * @var string
     */
    protected $priceSelector;

    /**
     * @var string
     */
    protected $quantitySelector;


    /**
     * @var Registry
     */
    protected $registry;

    /**
     * Simulator constructor.
     *
     * @param Context  $context
     * @param Registry $registry
     * @param array    $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        /** @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig */
        $scopeConfig = $this->_scopeConfig;
        $config = $scopeConfig->getValue('payment/paylater');

        $this->enabled = $config['active'];
        $this->publicKey = isset($config['public_key']) ? $config['public_key'] : '';
        $this->productSimulator = $config['product_simulator'];
        $this->minAmount = $config['min_amount'];
        $this->maxAmount = $config['max_amount'];
        $this->minInstallments = $config['min_installments'];
        $this->maxInstallments = $config['max_installments'];
        $this->priceSelector = $config['price_selector'];
        $this->quantitySelector = $config['quantity_selector'];
    }

    /**
     * @return Product
     */
    protected function getProduct()
    {
        if (is_null($this->product)) {
            $this->product = $this->registry->registry('product');
        }

        return $this->product;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return string
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @return int
     */
    public function getSimulatorType()
    {
        return $this->simulatorType;
    }

    /**
     * @return string
     */
    public function getPromotionProductExtra()
    {
        return $this->promotionProductExtra;
    }

    /**
     * @return bool
     */
    public function isProductInPromotion()
    {
        try {
            /** @var Category $category */
            $category = $this->getProduct()->getCategoryCollection()->addFieldToFilter(
                'name',
                array('eq' => self::PROMOTIONS_CATEGORY)
            )->getFirstItem();
        } catch (\Exception $exception) {
            return false;
        }

        return $category->getName() === self::PROMOTIONS_CATEGORY ? 1 : 0;
    }

    /**
     * @return float
     */
    public function getFinalPrice()
    {
        return $this->getProduct()->getFinalPrice();
    }

    /**
     * @return int
     */
    public function getProductSimulator()
    {
        return $this->productSimulator;
    }

    /**
     * @param int $productSimulator
     */
    public function setProductSimulator($productSimulator)
    {
        $this->productSimulator = $productSimulator;
    }

    /**
     * @return float
     */
    public function getMinAmount()
    {
        return $this->minAmount;
    }

    /**
     * @param float $minAmount
     */
    public function setMinAmount($minAmount)
    {
        $this->minAmount = $minAmount;
    }

    /**
     * @return float
     */
    public function getMaxAmount()
    {
        return $this->maxAmount;
    }

    /**
     * @param float $maxAmount
     */
    public function setMaxAmount($maxAmount)
    {
        $this->maxAmount = $maxAmount;
    }

    /**
     * @return int
     */
    public function getMinInstallments()
    {
        return $this->minInstallments;
    }

    /**
     * @param int $minInstallments
     */
    public function setMinInstallments($minInstallments)
    {
        $this->minInstallments = $minInstallments;
    }

    /**
     * @return int
     */
    public function getMaxInstallments()
    {
        return $this->maxInstallments;
    }

    /**
     * @param int $maxInstallments
     */
    public function setMaxInstallments($maxInstallments)
    {
        $this->maxInstallments = $maxInstallments;
    }

    /**
     * @return string
     */
    public function getPriceSelector()
    {
        return $this->priceSelector;
    }

    /**
     * @param string $priceSelector
     */
    public function setPriceSelector($priceSelector)
    {
        $this->priceSelector = $priceSelector;
    }

    /**
     * @return string
     */
    public function getQuantitySelector()
    {
        return $this->quantitySelector;
    }

    /**
     * @param string $quantitySelector
     */
    public function setQuantitySelector($quantitySelector)
    {
        $this->quantitySelector = $quantitySelector;
    }

}
