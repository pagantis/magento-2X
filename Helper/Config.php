<?php

namespace Clearpay\Clearpay\Helper;

/**
 * Class Config
 * @package Clearpay\Clearpay\Helper
 */
class Config
{
    /** DEFAULT_API_REGION */
    const DEFAULT_API_REGION = 'ES';

    /** DEFAULT_API_ENVIRONMENT */
    const DEFAULT_API_ENVIRONMENT = 'Sandbox';

    /**
     * @var Config
     */
    protected $config;

    /**
     * Config constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->config = $scopeConfig->getValue('payment/clearpay');
    }

    /**
     * @return mixed
     */
    public function getMerchantId()
    {
        $isDefined = isset($this->config, $this->config['clearpay_merchant_id']) && is_array($this->config);

        return ($isDefined) ? $this->config['clearpay_merchant_id'] : '';
    }

    /**
     * @return mixed
     */
    public function getSecretKey()
    {
        $isDefined = isset($this->config, $this->config['clearpay_merchant_key']) && is_array($this->config);

        return ($isDefined) ? $this->config['clearpay_merchant_key'] : '';
    }

    /**
     * @return mixed
     */
    public function getApiRegion()
    {
        $isDefined = isset($this->config, $this->config['clearpay_api_region']) && is_array($this->config);

        return ($isDefined) ? $this->config['clearpay_api_region'] : self::DEFAULT_API_REGION;
    }

    /**
     * @return mixed
     */
    public function getApiEnvironment()
    {
        $isDefined = isset($this->config, $this->config['clearpay_api_environment']) && is_array($this->config);

        return ($isDefined) ? $this->config['clearpay_api_environment'] : self::DEFAULT_API_ENVIRONMENT;
    }

    /**
     * @return array
     */
    public function getExcludedCategories()
    {
        $isDefined = isset($this->config, $this->config['clearpay_exclude_category']) && is_array($this->config);

        return ($isDefined) ? $this->config['clearpay_exclude_category'] : null;
    }

    /**
     * @return mixed|Config
     */
    public function getConfig()
    {
        return $this->config;
    }

}