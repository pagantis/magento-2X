<?php

namespace Pagantis\Pagantis\Model\Adminhtml\Source;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;
use Pagantis\Pagantis\Helper\MerchantProperties;

class Disable extends Field
{
    /** @var MerchantProperties $merchantProperties */
    protected $merchantProperties;

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setReadonly(true);
        if ($element->getId() === 'payment_us_pagantis_clearpay_min_amount') {
            $element->setValue($this->getMinAmount());
        } elseif ($element->getId() === 'payment_us_pagantis_clearpay_max_amount') {
            $element->setValue($this->getMaxAmount());
        }

        return $element->getElementHtml();
    }

    //GETTERS & SETTERS
    /**
     * @return MerchantProperties
     */
    public function getMerchantProperties()
    {
        return $this->merchantProperties;
    }

    /**
     * @param MerchantProperties $merchantProperties
     */
    public function setMerchantProperties($merchantProperties)
    {
        $this->merchantProperties = $merchantProperties;
    }

    //CLASS METHODS
    /**
     * @return float|string
     */
    public function getMinAmount()
    {
        if ($this->merchantProperties === null) {
            $this->merchantProperties = new MerchantProperties();
        }

        return $this->merchantProperties->getMinAmount();
    }

    /**
     * @return float|string
     */
    public function getMaxAmount()
    {
        if ($this->merchantProperties === null) {
            $this->merchantProperties = new MerchantProperties();
        }

        return $this->merchantProperties->getMaxAmount();
    }

}
