<?php

namespace Clearpay\Clearpay\Helper;

use Afterpay\SDK\HTTP\Request\GetConfiguration;
use Afterpay\SDK\MerchantAccount;

/**
 * Class MerchantProperties
 * @package Clearpay\Clearpay\Helper
 */
class MerchantProperties
{
    /**
     * DEFAULT MIN AMOUNT
     */
    const DEF_MIN_AMOUNT = 10.00;

    /**
     * DEFAULT MAX AMOUNT
     */
    const DEF_MAX_AMOUNT = 800.00;

    /**
     * @var Config $config
     */
    protected $moduleConfig;

    /**
     * @var mixed|null $merchantConfig
     */
    protected $merchantConfig;

    /**
     * MerchantProperties constructor.
     */
    public function __construct()
    {
        $this->moduleConfig = $this->getModuleConfig();
        $this->merchantConfig = $this->getMerchantConfiguration();
    }

    /**
     * @return mixed|null
     * @throws \Afterpay\SDK\Exception\InvalidArgumentException
     * @throws \Afterpay\SDK\Exception\NetworkException
     * @throws \Afterpay\SDK\Exception\ParsingException
     */
    private function getMerchantConfiguration()
    {
        $clearpayMerchantAccount = new MerchantAccount();
        $clearpayMerchantAccount
            ->setMerchantId($this->moduleConfig->getMerchantId())
            ->setSecretKey($this->moduleConfig->getSecretKey())
            ->setCountryCode($this->moduleConfig->getApiRegion())
            ->setApiEnvironment($this->moduleConfig->getApiEnvironment())
        ;

        $getConfigurationRequest = new GetConfiguration();
        $getConfigurationRequest
            ->setMerchantAccount($clearpayMerchantAccount)
            ->send();
        $configurationResponse = $getConfigurationRequest->getResponse()->getParsedBody();

        if (is_array($configurationResponse)) {
            return array_shift($configurationResponse);
        } else {
            return null;
        }
    }

    /**
     * @return mixed
     */
    private function getModuleConfig()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig = $objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $config = new Config($scopeConfig);

        return $config;
    }

    /**
     * @return float
     */
    public function getMinAmount()
    {
        if ($this->merchantConfig!=null) {
            return $this->merchantConfig->minimumAmount->amount;
        }

        return self::DEF_MIN_AMOUNT;
    }

    /**
     * @return float
     */
    public function getMaxAmount()
    {
        if ($this->merchantConfig!=null) {
            return $this->merchantConfig->maximumAmount->amount;
        }

        return self::DEF_MAX_AMOUNT;
    }
}