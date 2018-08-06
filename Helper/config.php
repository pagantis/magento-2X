<?php

namespace DigitalOrigin\Pmt\Helper;

class Config
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function getConfig()
    {
        return $this->scopeConfig->getValue('payment/paylater');
    }
}