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
    protected $simulatorType;

    /**
     * @var string
     */
    protected $promotionProductExtra;

    /**
     * @var Product
     */
    protected $product;

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
        $isProduction = $config['sandbox'];
        $this->publicKey = $isProduction ? $config['prod_public_key'] : $config['test_public_key'];
        $this->simulatorType = $config['product_simulator'];
        $this->enabled = $config['active'];
        $this->promotionProductExtra = $config['promotion_extra'];
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
}
