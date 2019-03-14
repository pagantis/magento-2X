<?php

namespace DigitalOrigin\Pmt\Helper;

/**
 * Class Config
 * @package DigitalOrigin\Pmt\Helper
 */
class Config
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Config constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->scopeConfig->getValue('payment/pagantis');
    }
}