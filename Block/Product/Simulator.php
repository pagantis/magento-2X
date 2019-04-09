<?php

namespace Pagantis\Pagantis\Block\Product;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Pagantis\Pagantis\Helper\ExtraConfig;

/**
 * Class Simulator
 * @package Pagantis\Pagantis\Block\Product
 */
class Simulator extends Template
{
    const PROMOTIONS_CATEGORY = 'pagantis-promotion-product';

    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @var string
     */
    protected $publicKey;

    /**
     * @var string
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
     * @var int
     */
    protected $minInstallments;

    /**
     * @var string
     */
    protected $priceSelector;

    /**
     * @var string
     */
    protected $quantitySelector;

    /**
     * @var String
     */
    protected $positionSelector;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Config
     */
    protected $extraConfig;

    /**
     * @var String
     */
    protected $simulatorType;

    /**
     * Simulator constructor.
     *
     * @param Context  $context
     * @param Registry $registry
     * @param ExtraConfig   $extraConfig
     * @param array    $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtraConfig $extraConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        /** @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig */
        $scopeConfig = $this->_scopeConfig;
        $config = $scopeConfig->getValue('payment/pagantis');

        $this->enabled = $config['active'];
        $this->publicKey = isset($config['pagantis_public_key']) ? $config['pagantis_public_key'] : '';
        $this->productSimulator = $config['product_simulator'];
        $this->extraConfig = $extraConfig->getExtraConfig();
        $this->minAmount = $this->extraConfig['PAGANTIS_DISPLAY_MIN_AMOUNT'];
        $this->minInstallments = $this->extraConfig['PAGANTIS_SIMULATOR_START_INSTALLMENTS'];
        $this->priceSelector = $this->extraConfig['PAGANTIS_SIMULATOR_CSS_PRICE_SELECTOR'];
        $this->quantitySelector = $this->extraConfig['PAGANTIS_SIMULATOR_CSS_QUANTITY_SELECTOR'];
        $this->positionSelector = $this->extraConfig['PAGANTIS_SIMULATOR_CSS_POSITION_SELECTOR'];
        $this->simulatorType = $this->extraConfig['PAGANTIS_SIMULATOR_DISPLAY_TYPE'];
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
     * @return array|false|string
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

    /**
     * @return String
     */
    public function getPositionSelector()
    {
        return $this->positionSelector;
    }

    /**
     * @param String $positionSelector
     */
    public function setPositionSelector($positionSelector)
    {
        $this->positionSelector = $positionSelector;
    }


    /**
     * @return String
     */
    public function getSimulatorType()
    {
        return $this->simulatorType;
    }

    /**
     * @param String $simulatorType
     */
    public function setSimulatorType($simulatorType)
    {
        $this->simulatorType = $simulatorType;
    }
}
