<?php

namespace DigitalOrigin\Pmt\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use DigitalOrigin\Pmt\Helper\ExtraConfig;

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
     * @var String
     */
    protected $extraConfig;

    /**
     * ConfigProvider constructor.
     *
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param Session                      $checkoutSession
     * @param ExtraConfig                  $extraConfig
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper,
        Session $checkoutSession,
        ExtraConfig $extraConfig
    ) {
        $this->method = $paymentHelper->getMethodInstance(self::CODE);
        $this->checkoutSession = $checkoutSession;
        $this->extraConfig = $extraConfig->getExtraConfig();
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
                    'displayMode' => $this->method->getConfigData('display_mode'),
                    'title' => $this->__($this->extraConfig['PMT_TITLE']),
                    'subtitle' => $this->__($this->extraConfig['PMT_TITLE_EXTRA'])
                ],
            ],
        ];
    }
}
