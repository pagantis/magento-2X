<?php

namespace DigitalOrigin\Pmt\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;

/**
 * Class ConfigProvider
 * @package DigitalOrigin\Pmt\Model\Ui
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'paylater';

    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $method;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * ConfigProvider constructor.
     *
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param Session                      $checkoutSession
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(\Magento\Payment\Helper\Data $paymentHelper, Session $checkoutSession)
    {
        $this->method = $paymentHelper->getMethodInstance(self::CODE);
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $quote = $this->checkoutSession->getQuote();

        return [
            'payment' => [
                self::CODE => [
                    'total' => $quote->getGrandTotal(),
                    'publicKey' => $this->method->getConfigData('public_key'),
                    'secretKey' => $this->method->getConfigData('secret_key'),
                    'pmtType' => $this->method->getConfigData('checkout_simulator'),
                    'pmtMaxIns' => $this->method->getConfigData('max_installments'),
                    'pmtNumQuota' => $this->method->getConfigData('min_installments'),
                    'displayMode' => $this->method->getConfigData('display_mode'),
                ],
            ],
        ];
    }
}
